<?php
declare(strict_types=1);

/**
 * Dedicated contact inbox database.
 *
 * Storage file:
 *   data/contact-inbox.sqlite
 */
function get_contact_inbox_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbDir = dirname(__DIR__) . '/data';
    $dbPath = $dbDir . '/contact-inbox.sqlite';

    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');

    init_contact_inbox_schema($pdo);

    return $pdo;
}

function init_contact_inbox_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contact_submissions (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_ref      TEXT    NOT NULL UNIQUE,
            inquiry_type    TEXT    NOT NULL CHECK (inquiry_type IN ('general','media','speaking','reader','arc')),
            full_name       TEXT    NOT NULL,
            email           TEXT    NOT NULL,
            subject         TEXT    NOT NULL,
            message         TEXT    NOT NULL,
            consent_contact INTEGER NOT NULL DEFAULT 1 CHECK (consent_contact IN (0,1)),
            ip_address      TEXT    NOT NULL DEFAULT '',
            user_agent      TEXT    NOT NULL DEFAULT '',
            status          TEXT    NOT NULL DEFAULT 'new' CHECK (status IN ('new','reviewed','resolved','spam')),
            submitted_at    TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contact_submission_events (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_ref  TEXT    NOT NULL REFERENCES contact_submissions(ticket_ref) ON DELETE CASCADE,
            event_type  TEXT    NOT NULL CHECK (event_type IN ('status_change','note','system')),
            from_status TEXT,
            to_status   TEXT,
            note        TEXT    NOT NULL DEFAULT '',
            actor       TEXT    NOT NULL DEFAULT 'system',
            created_at  TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contact_submission_archive (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            submission_id INTEGER NOT NULL,
            ticket_ref   TEXT    NOT NULL,
            payload_json TEXT    NOT NULL,
            events_json  TEXT    NOT NULL DEFAULT '[]',
            archived_by  TEXT    NOT NULL DEFAULT 'system',
            archived_at  TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_contact_submissions_status ON contact_submissions(status)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_contact_submissions_email ON contact_submissions(email)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_contact_submissions_submitted_at ON contact_submissions(submitted_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_contact_submissions_type ON contact_submissions(inquiry_type)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_contact_events_ticket_ref ON contact_submission_events(ticket_ref)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_contact_events_created_at ON contact_submission_events(created_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_contact_archive_ticket_ref ON contact_submission_archive(ticket_ref)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_contact_archive_archived_at ON contact_submission_archive(archived_at)');
}

function ajj_contact_generate_ticket_ref(): string
{
    return 'AJJ-CS-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

function ajj_contact_log_event(
    PDO $pdo,
    string $ticketRef,
    string $eventType,
    ?string $fromStatus,
    ?string $toStatus,
    string $note,
    string $actor
): void {
    $allowed = ['status_change', 'note', 'system'];
    if (!in_array($eventType, $allowed, true)) {
        $eventType = 'system';
    }

    $stmt = $pdo->prepare(
        'INSERT INTO contact_submission_events (ticket_ref, event_type, from_status, to_status, note, actor)
         VALUES (:ticket_ref, :event_type, :from_status, :to_status, :note, :actor)'
    );
    $stmt->execute([
        ':ticket_ref' => $ticketRef,
        ':event_type' => $eventType,
        ':from_status' => $fromStatus,
        ':to_status' => $toStatus,
        ':note' => $note,
        ':actor' => $actor,
    ]);
}
