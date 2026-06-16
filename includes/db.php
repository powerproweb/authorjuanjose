<?php
declare(strict_types=1);

/**
 * Database — SQLite via PDO.
 *
 * Usage:
 *   require_once __DIR__ . '/db.php';
 *   $pdo = get_db();
 *   $stmt = $pdo->prepare('SELECT * FROM members WHERE id = ?');
 *
 * The database file lives at data/arc.sqlite.
 * Tables are created automatically on first connection.
 */

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $db_dir  = dirname(__DIR__) . '/data';
    $db_path = $db_dir . '/arc.sqlite';

    // Ensure data directory exists
    if (!is_dir($db_dir)) {
        mkdir($db_dir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . $db_path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Enable WAL mode for better concurrent read performance
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');

    // Auto-create tables if they don't exist
    init_schema($pdo);

    return $pdo;
}

function init_schema(PDO $pdo): void
{
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS members (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT    NOT NULL,
            email         TEXT    NOT NULL UNIQUE COLLATE NOCASE,
            password_hash TEXT    NOT NULL DEFAULT "",
            language      TEXT    NOT NULL DEFAULT "English",
            country       TEXT    NOT NULL DEFAULT "",
            referral      TEXT    NOT NULL DEFAULT "",
            interests     TEXT    NOT NULL DEFAULT "",
            status        TEXT    NOT NULL DEFAULT "pending" CHECK(status IN ("pending","active","suspended")),
            tier          INTEGER NOT NULL DEFAULT 0 CHECK(tier BETWEEN 0 AND 4),
            review_count  INTEGER NOT NULL DEFAULT 0,
            joined_at     TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            approved_at   TEXT
        )
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS campaigns (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            title           TEXT    NOT NULL,
            book_slug       TEXT    NOT NULL DEFAULT "",
            description     TEXT    NOT NULL DEFAULT "",
            arc_format      TEXT    NOT NULL DEFAULT "ebook" CHECK(arc_format IN ("ebook","pdf","epub")),
            review_deadline TEXT,
            status          TEXT    NOT NULL DEFAULT "draft" CHECK(status IN ("draft","active","closed")),
            language        TEXT    NOT NULL DEFAULT "both" CHECK(language IN ("English","Spanish","both")),
            created_at      TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');

    // Migration: add language column if missing (existing DBs)
    $cols = $pdo->query('PRAGMA table_info(campaigns)')->fetchAll();
    $has_language = false;
    foreach ($cols as $c) { if ($c['name'] === 'language') { $has_language = true; break; } }
    if (!$has_language) {
        $pdo->exec('ALTER TABLE campaigns ADD COLUMN language TEXT NOT NULL DEFAULT "both"');
    }

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS campaign_invites (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            campaign_id INTEGER NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
            member_id   INTEGER NOT NULL REFERENCES members(id)   ON DELETE CASCADE,
            status      TEXT    NOT NULL DEFAULT "invited" CHECK(status IN ("invited","accepted","declined","completed")),
            invited_at  TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            accepted_at TEXT,
            UNIQUE(campaign_id, member_id)
        )
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS reviews (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            member_id    INTEGER NOT NULL REFERENCES members(id)    ON DELETE CASCADE,
            campaign_id  INTEGER NOT NULL REFERENCES campaigns(id)  ON DELETE CASCADE,
            platform     TEXT    NOT NULL DEFAULT "amazon" CHECK(platform IN ("amazon","goodreads","other")),
            review_url   TEXT    NOT NULL DEFAULT "",
            review_text  TEXT    NOT NULL DEFAULT "",
            submitted_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            verified     INTEGER NOT NULL DEFAULT 0 CHECK(verified IN (0,1))
        )
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS distinctions (
            id        INTEGER PRIMARY KEY AUTOINCREMENT,
            member_id INTEGER NOT NULL REFERENCES members(id) ON DELETE CASCADE,
            tier      INTEGER NOT NULL CHECK(tier BETWEEN 1 AND 4),
            earned_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');

    // Phase 4 tables
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            email         TEXT    NOT NULL UNIQUE COLLATE NOCASE,
            name          TEXT    NOT NULL DEFAULT "",
            interest      TEXT    NOT NULL DEFAULT "both" CHECK(interest IN ("fiction","non-fiction","both")),
            subscribed_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS email_queue (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            recipient_email TEXT    NOT NULL,
            recipient_name  TEXT    NOT NULL DEFAULT "",
            template_key    TEXT    NOT NULL,
            language        TEXT    NOT NULL DEFAULT "English",
            scheduled_at    TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            sent_at         TEXT,
            status          TEXT    NOT NULL DEFAULT "pending" CHECK(status IN ("pending","sent","failed")),
            error_message   TEXT    NOT NULL DEFAULT ""
        )
    ');

    // Author-only mailing list system
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS mailing_lists (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            slug        TEXT    NOT NULL UNIQUE COLLATE NOCASE,
            name        TEXT    NOT NULL,
            description TEXT    NOT NULL DEFAULT "",
            is_active   INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0,1)),
            sort_order  INTEGER NOT NULL DEFAULT 100,
            created_at  TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS mailing_contacts (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            email      TEXT    NOT NULL UNIQUE COLLATE NOCASE,
            name       TEXT    NOT NULL DEFAULT "",
            status     TEXT    NOT NULL DEFAULT "active" CHECK(status IN ("active","unsubscribed","suppressed","bounced")),
            source     TEXT    NOT NULL DEFAULT "",
            created_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS mailing_list_memberships (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            contact_id      INTEGER NOT NULL REFERENCES mailing_contacts(id) ON DELETE CASCADE,
            list_id         INTEGER NOT NULL REFERENCES mailing_lists(id) ON DELETE CASCADE,
            status          TEXT    NOT NULL DEFAULT "active" CHECK(status IN ("active","unsubscribed")),
            source          TEXT    NOT NULL DEFAULT "",
            subscribed_at   TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            unsubscribed_at TEXT,
            updated_at      TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(contact_id, list_id)
        )
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS schema_migrations (
            migration_key TEXT PRIMARY KEY,
            completed_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS mailing_duplicate_ignored_clusters (
            normalized_email TEXT PRIMARY KEY COLLATE NOCASE,
            ignored_by       TEXT NOT NULL DEFAULT "",
            ignored_reason   TEXT NOT NULL DEFAULT "",
            ignored_at       TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS mailing_contact_merge_archive (
            id                   INTEGER PRIMARY KEY AUTOINCREMENT,
            winner_contact_id    INTEGER NOT NULL REFERENCES mailing_contacts(id) ON DELETE CASCADE,
            merged_contact_id    INTEGER NOT NULL,
            merged_contact_email TEXT    NOT NULL,
            merged_contact_name  TEXT    NOT NULL DEFAULT "",
            merged_contact_status TEXT   NOT NULL DEFAULT "",
            merged_contact_source TEXT   NOT NULL DEFAULT "",
            merged_payload       TEXT    NOT NULL DEFAULT "",
            merged_by            TEXT    NOT NULL DEFAULT "",
            merged_at            TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS mailing_operation_log (
            id                 INTEGER PRIMARY KEY AUTOINCREMENT,
            action             TEXT    NOT NULL,
            actor              TEXT    NOT NULL DEFAULT "",
            contact_id         INTEGER REFERENCES mailing_contacts(id) ON DELETE SET NULL,
            related_contact_id INTEGER REFERENCES mailing_contacts(id) ON DELETE SET NULL,
            list_id            INTEGER REFERENCES mailing_lists(id) ON DELETE SET NULL,
            from_list_id       INTEGER REFERENCES mailing_lists(id) ON DELETE SET NULL,
            to_list_id         INTEGER REFERENCES mailing_lists(id) ON DELETE SET NULL,
            details            TEXT    NOT NULL DEFAULT "",
            created_at         TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS notifications (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            member_id  INTEGER NOT NULL REFERENCES members(id) ON DELETE CASCADE,
            type       TEXT    NOT NULL DEFAULT "system" CHECK(type IN ("invite","reminder","promotion","system")),
            message    TEXT    NOT NULL,
            read       INTEGER NOT NULL DEFAULT 0 CHECK(read IN (0,1)),
            created_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS gallery_uploads (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            member_id      INTEGER NOT NULL REFERENCES members(id) ON DELETE CASCADE,
            image_path     TEXT    NOT NULL,
            thumbnail_path TEXT    NOT NULL DEFAULT "",
            caption        TEXT    NOT NULL DEFAULT "",
            status         TEXT    NOT NULL DEFAULT "pending" CHECK(status IN ("pending","approved","rejected")),
            uploaded_at    TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            approved_at    TEXT
        )
    ');

    // Performance indexes
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_gallery_status ON gallery_uploads(status)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_notif_member_read ON notifications(member_id, read)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_email_queue_status ON email_queue(status)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mailing_lists_active_order ON mailing_lists(is_active, sort_order, id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mailing_contacts_status ON mailing_contacts(status)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mailing_memberships_list_status ON mailing_list_memberships(list_id, status)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mailing_memberships_contact_status ON mailing_list_memberships(contact_id, status)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mailing_ignored_clusters_ignored_at ON mailing_duplicate_ignored_clusters(ignored_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mailing_merge_archive_winner ON mailing_contact_merge_archive(winner_contact_id, merged_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mailing_operation_log_contact ON mailing_operation_log(contact_id, created_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mailing_operation_log_action ON mailing_operation_log(action, created_at)');

    // Phase 5 tables
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS search_index (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            content_type TEXT NOT NULL CHECK(content_type IN ("fiction","non-fiction","journal","page")),
            content_id   TEXT NOT NULL,
            title        TEXT NOT NULL,
            body_text    TEXT NOT NULL DEFAULT "",
            url          TEXT NOT NULL,
            indexed_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(content_type, content_id)
        )
    ');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_search_type ON search_index(content_type)');

    seed_author_mailing_lists($pdo);
    migrate_newsletter_subscribers_to_mailing_lists($pdo);
}

// ---------------------------------------------------------------------------
//  Mailing list helpers
// ---------------------------------------------------------------------------

function seed_author_mailing_lists(PDO $pdo): void
{
    $defaults = [
        ['slug' => 'author-news', 'name' => 'Author News', 'description' => 'General updates and announcements.', 'sort_order' => 10],
        ['slug' => 'fiction', 'name' => 'Fiction Releases', 'description' => 'Fiction launch and story-world updates.', 'sort_order' => 20],
        ['slug' => 'non-fiction', 'name' => 'Non-Fiction Releases', 'description' => 'Essays, resources, and non-fiction updates.', 'sort_order' => 30],
        ['slug' => 'arc', 'name' => 'ARC Reader Club', 'description' => 'Advance reader opportunities and club updates.', 'sort_order' => 40],
        ['slug' => 'events', 'name' => 'Events and Appearances', 'description' => 'Readings, speaking events, and appearances.', 'sort_order' => 50],
        ['slug' => 'media', 'name' => 'Media and Press', 'description' => 'Press and interview related updates.', 'sort_order' => 60],
    ];

    $insert = $pdo->prepare(
        'INSERT OR IGNORE INTO mailing_lists (slug, name, description, sort_order) VALUES (?, ?, ?, ?)'
    );

    foreach ($defaults as $list) {
        $insert->execute([
            $list['slug'],
            $list['name'],
            $list['description'],
            $list['sort_order'],
        ]);
    }
}

function get_active_mailing_lists(PDO $pdo): array
{
    return $pdo->query('
        SELECT id, slug, name, description, sort_order
        FROM mailing_lists
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ')->fetchAll();
}

function get_active_mailing_list_ids_by_slug(PDO $pdo): array
{
    $rows = get_active_mailing_lists($pdo);
    $map = [];
    foreach ($rows as $row) {
        $map[strtolower((string)$row['slug'])] = (int)$row['id'];
    }

    return $map;
}

function normalize_mailing_list_slugs(array $slugs): array
{
    $normalized = [];
    foreach ($slugs as $slug) {
        $value = strtolower(trim((string)$slug));
        if ($value === '') {
            continue;
        }
        $normalized[$value] = true;
    }

    return array_keys($normalized);
}

function map_legacy_newsletter_interest_to_list_slugs(string $interest): array
{
    return match ($interest) {
        'fiction' => ['author-news', 'fiction'],
        'non-fiction' => ['author-news', 'non-fiction'],
        default => ['author-news', 'fiction', 'non-fiction'],
    };
}

function has_schema_migration(PDO $pdo, string $migrationKey): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE migration_key = ? LIMIT 1');
    $stmt->execute([$migrationKey]);
    return $stmt->fetchColumn() !== false;
}

function mark_schema_migration(PDO $pdo, string $migrationKey): void
{
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO schema_migrations (migration_key) VALUES (?)');
    $stmt->execute([$migrationKey]);
}

function migrate_newsletter_subscribers_to_mailing_lists(PDO $pdo): void
{
    $migrationKey = 'mailing_legacy_newsletter_subscribers_to_lists_v1';
    if (has_schema_migration($pdo, $migrationKey)) {
        return;
    }
    $legacyCount = (int)$pdo->query('SELECT COUNT(*) FROM newsletter_subscribers')->fetchColumn();
    if ($legacyCount === 0) {
        mark_schema_migration($pdo, $migrationKey);
        return;
    }

    $listMap = get_active_mailing_list_ids_by_slug($pdo);
    if ($listMap === []) {
        return;
    }

    $legacyRows = $pdo->query('SELECT email, name, interest FROM newsletter_subscribers')->fetchAll();
    if ($legacyRows === []) {
        mark_schema_migration($pdo, $migrationKey);
        return;
    }

    $insertContact = $pdo->prepare(
        'INSERT OR IGNORE INTO mailing_contacts (email, name, source) VALUES (?, ?, "legacy_newsletter_migration")'
    );
    $updateContactName = $pdo->prepare(
        'UPDATE mailing_contacts SET name = ?, updated_at = CURRENT_TIMESTAMP WHERE email = ? AND name = ""'
    );
    $findContact = $pdo->prepare('SELECT id FROM mailing_contacts WHERE email = ?');
    $insertMembership = $pdo->prepare(
        'INSERT OR IGNORE INTO mailing_list_memberships (contact_id, list_id, status, source) VALUES (?, ?, "active", "legacy_newsletter_migration")'
    );

    foreach ($legacyRows as $row) {
        $email = strtolower(trim((string)($row['email'] ?? '')));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            continue;
        }

        $name = trim((string)($row['name'] ?? ''));
        $interest = strtolower(trim((string)($row['interest'] ?? 'both')));
        $slugs = map_legacy_newsletter_interest_to_list_slugs($interest);

        $insertContact->execute([$email, $name]);
        if ($name !== '') {
            $updateContactName->execute([$name, $email]);
        }

        $findContact->execute([$email]);
        $contactId = (int)$findContact->fetchColumn();
        if ($contactId <= 0) {
            continue;
        }

        foreach ($slugs as $slug) {
            $listId = $listMap[$slug] ?? 0;
            if ($listId <= 0) {
                continue;
            }
            $insertMembership->execute([$contactId, $listId]);
        }
    }

    mark_schema_migration($pdo, $migrationKey);
}

function normalize_mailing_email(string $email): string
{
    return strtolower(trim($email));
}

function mailing_get_admin_actor_label(): string
{
    $authUser = trim((string)($_SERVER['PHP_AUTH_USER'] ?? ''));
    if ($authUser !== '') {
        return $authUser;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $sessionUser = trim((string)($_SESSION['site_auth_user'] ?? ($_SESSION['admin_user'] ?? '')));
        if ($sessionUser !== '') {
            return $sessionUser;
        }
    }

    return 'admin';
}

function mailing_log_operation(PDO $pdo, string $action, string $actor, array $context = []): void
{
    $toNullableInt = static function (mixed $value): ?int {
        $asInt = (int)$value;
        return $asInt > 0 ? $asInt : null;
    };

    $details = $context['details'] ?? '';
    if (is_array($details) || is_object($details)) {
        $encoded = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $details = $encoded === false ? '' : $encoded;
    }

    $stmt = $pdo->prepare('
        INSERT INTO mailing_operation_log (
            action, actor, contact_id, related_contact_id, list_id, from_list_id, to_list_id, details
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        trim($action),
        trim($actor),
        $toNullableInt($context['contact_id'] ?? null),
        $toNullableInt($context['related_contact_id'] ?? null),
        $toNullableInt($context['list_id'] ?? null),
        $toNullableInt($context['from_list_id'] ?? null),
        $toNullableInt($context['to_list_id'] ?? null),
        (string)$details,
    ]);
}

function mailing_get_contact_row(PDO $pdo, int $contactId): ?array
{
    if ($contactId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare('
        SELECT id, email, name, status, source, created_at, updated_at
        FROM mailing_contacts
        WHERE id = ?
        LIMIT 1
    ');
    $stmt->execute([$contactId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function mailing_get_contact_memberships(PDO $pdo, int $contactId): array
{
    if ($contactId <= 0) {
        return [];
    }

    $stmt = $pdo->prepare('
        SELECT
            mlm.id,
            mlm.contact_id,
            mlm.list_id,
            mlm.status,
            mlm.source,
            mlm.subscribed_at,
            mlm.unsubscribed_at,
            mlm.updated_at,
            ml.slug AS list_slug,
            ml.name AS list_name
        FROM mailing_list_memberships mlm
        INNER JOIN mailing_lists ml ON ml.id = mlm.list_id
        WHERE mlm.contact_id = ?
        ORDER BY ml.sort_order ASC, ml.id ASC
    ');
    $stmt->execute([$contactId]);
    return $stmt->fetchAll();
}

function mailing_get_active_membership_count(PDO $pdo, int $contactId): int
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM mailing_list_memberships
        WHERE contact_id = ? AND status = "active"
    ');
    $stmt->execute([$contactId]);
    return (int)$stmt->fetchColumn();
}

function mailing_sync_contact_status(PDO $pdo, int $contactId): void
{
    if ($contactId <= 0) {
        return;
    }

    $contact = mailing_get_contact_row($pdo, $contactId);
    if ($contact === null) {
        return;
    }

    $currentStatus = (string)$contact['status'];
    if ($currentStatus === 'suppressed' || $currentStatus === 'bounced') {
        return;
    }

    $activeMemberships = mailing_get_active_membership_count($pdo, $contactId);
    $targetStatus = $activeMemberships > 0 ? 'active' : 'unsubscribed';
    if ($targetStatus === $currentStatus) {
        return;
    }

    $stmt = $pdo->prepare('
        UPDATE mailing_contacts
        SET status = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ');
    $stmt->execute([$targetStatus, $contactId]);
}

function mailing_set_contact_memberships(PDO $pdo, int $contactId, array $selectedListIds, string $source, string $actor): void
{
    $contact = mailing_get_contact_row($pdo, $contactId);
    if ($contact === null) {
        throw new RuntimeException('Contact not found.');
    }

    $selectedMap = [];
    foreach ($selectedListIds as $listIdRaw) {
        $listId = (int)$listIdRaw;
        if ($listId > 0) {
            $selectedMap[$listId] = true;
        }
    }

    $activeLists = get_active_mailing_lists($pdo);
    $activeListIds = array_map(static fn(array $row): int => (int)$row['id'], $activeLists);

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $reactivateMembership = $pdo->prepare('
            UPDATE mailing_list_memberships
            SET status = "active", unsubscribed_at = NULL, updated_at = CURRENT_TIMESTAMP, source = ?
            WHERE contact_id = ? AND list_id = ?
        ');
        $insertMembership = $pdo->prepare('
            INSERT OR IGNORE INTO mailing_list_memberships (contact_id, list_id, status, source)
            VALUES (?, ?, "active", ?)
        ');
        $unsubscribeMembership = $pdo->prepare('
            UPDATE mailing_list_memberships
            SET status = "unsubscribed", unsubscribed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP, source = ?
            WHERE contact_id = ? AND list_id = ? AND status != "unsubscribed"
        ');

        foreach ($activeListIds as $listId) {
            if (isset($selectedMap[$listId])) {
                $reactivateMembership->execute([$source, $contactId, $listId]);
                $insertMembership->execute([$contactId, $listId, $source]);
            } else {
                $unsubscribeMembership->execute([$source, $contactId, $listId]);
            }
        }

        mailing_sync_contact_status($pdo, $contactId);
        mailing_log_operation($pdo, 'membership_set', $actor, [
            'contact_id' => $contactId,
            'details' => [
                'source' => $source,
                'selected_list_ids' => array_keys($selectedMap),
            ],
        ]);

        if ($ownsTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function mailing_copy_contact_to_list(PDO $pdo, int $contactId, int $toListId, string $actor, string $source = 'admin_copy'): void
{
    if ($contactId <= 0 || $toListId <= 0) {
        throw new InvalidArgumentException('Invalid contact/list for copy operation.');
    }

    $contact = mailing_get_contact_row($pdo, $contactId);
    if ($contact === null) {
        throw new RuntimeException('Contact not found.');
    }

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $reactivateMembership = $pdo->prepare('
            UPDATE mailing_list_memberships
            SET status = "active", unsubscribed_at = NULL, updated_at = CURRENT_TIMESTAMP, source = ?
            WHERE contact_id = ? AND list_id = ?
        ');
        $insertMembership = $pdo->prepare('
            INSERT OR IGNORE INTO mailing_list_memberships (contact_id, list_id, status, source)
            VALUES (?, ?, "active", ?)
        ');
        $reactivateMembership->execute([$source, $contactId, $toListId]);
        $insertMembership->execute([$contactId, $toListId, $source]);

        mailing_sync_contact_status($pdo, $contactId);
        mailing_log_operation($pdo, 'membership_copied', $actor, [
            'contact_id' => $contactId,
            'to_list_id' => $toListId,
            'details' => ['source' => $source],
        ]);

        if ($ownsTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function mailing_move_contact_between_lists(PDO $pdo, int $contactId, int $fromListId, int $toListId, string $actor, string $source = 'admin_move'): void
{
    if ($contactId <= 0 || $fromListId <= 0 || $toListId <= 0 || $fromListId === $toListId) {
        throw new InvalidArgumentException('Invalid move parameters.');
    }

    $contact = mailing_get_contact_row($pdo, $contactId);
    if ($contact === null) {
        throw new RuntimeException('Contact not found.');
    }

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $unsubscribeFrom = $pdo->prepare('
            UPDATE mailing_list_memberships
            SET status = "unsubscribed", unsubscribed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP, source = ?
            WHERE contact_id = ? AND list_id = ? AND status != "unsubscribed"
        ');
        $unsubscribeFrom->execute([$source, $contactId, $fromListId]);

        mailing_copy_contact_to_list($pdo, $contactId, $toListId, $actor, $source);

        mailing_sync_contact_status($pdo, $contactId);
        mailing_log_operation($pdo, 'membership_moved', $actor, [
            'contact_id' => $contactId,
            'from_list_id' => $fromListId,
            'to_list_id' => $toListId,
            'details' => ['source' => $source],
        ]);

        if ($ownsTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function mailing_unsubscribe_contact_from_list(PDO $pdo, int $contactId, int $listId, string $actor, string $source = 'admin_unsubscribe'): void
{
    if ($contactId <= 0 || $listId <= 0) {
        throw new InvalidArgumentException('Invalid unsubscribe parameters.');
    }

    $contact = mailing_get_contact_row($pdo, $contactId);
    if ($contact === null) {
        throw new RuntimeException('Contact not found.');
    }

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $unsubscribeStmt = $pdo->prepare('
            UPDATE mailing_list_memberships
            SET status = "unsubscribed", unsubscribed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP, source = ?
            WHERE contact_id = ? AND list_id = ? AND status != "unsubscribed"
        ');
        $unsubscribeStmt->execute([$source, $contactId, $listId]);

        $insertUnsubscribed = $pdo->prepare('
            INSERT OR IGNORE INTO mailing_list_memberships (
                contact_id, list_id, status, source, unsubscribed_at, updated_at
            ) VALUES (?, ?, "unsubscribed", ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ');
        $insertUnsubscribed->execute([$contactId, $listId, $source]);

        mailing_sync_contact_status($pdo, $contactId);
        mailing_log_operation($pdo, 'membership_unsubscribed', $actor, [
            'contact_id' => $contactId,
            'list_id' => $listId,
            'details' => ['source' => $source],
        ]);

        if ($ownsTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function mailing_bulk_apply_action(PDO $pdo, array $contactIds, string $action, int $fromListId, int $toListId, string $actor): int
{
    $normalizedContactIds = [];
    foreach ($contactIds as $contactIdRaw) {
        $contactId = (int)$contactIdRaw;
        if ($contactId > 0) {
            $normalizedContactIds[$contactId] = true;
        }
    }
    $contactIdList = array_keys($normalizedContactIds);
    if ($contactIdList === []) {
        return 0;
    }

    $processed = 0;
    $pdo->beginTransaction();
    try {
        foreach ($contactIdList as $contactId) {
            if ($action === 'move') {
                mailing_move_contact_between_lists($pdo, $contactId, $fromListId, $toListId, $actor, 'admin_bulk_move');
            } elseif ($action === 'copy') {
                mailing_copy_contact_to_list($pdo, $contactId, $toListId, $actor, 'admin_bulk_copy');
            } elseif ($action === 'unsubscribe') {
                mailing_unsubscribe_contact_from_list($pdo, $contactId, $fromListId, $actor, 'admin_bulk_unsubscribe');
            } else {
                throw new InvalidArgumentException('Unsupported bulk action.');
            }
            $processed++;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return $processed;
}

function mailing_get_duplicate_clusters(PDO $pdo, bool $includeIgnored = false): array
{
    $query = '
        SELECT
            d.normalized_email,
            d.duplicate_count,
            ig.normalized_email AS ignored_email,
            COALESCE(ig.ignored_by, "") AS ignored_by,
            COALESCE(ig.ignored_reason, "") AS ignored_reason,
            COALESCE(ig.ignored_at, "") AS ignored_at
        FROM (
            SELECT LOWER(TRIM(email)) AS normalized_email, COUNT(*) AS duplicate_count
            FROM mailing_contacts
            GROUP BY LOWER(TRIM(email))
            HAVING COUNT(*) > 1
        ) d
        LEFT JOIN mailing_duplicate_ignored_clusters ig ON ig.normalized_email = d.normalized_email
    ';
    if (!$includeIgnored) {
        $query .= ' WHERE ig.normalized_email IS NULL';
    }
    $query .= ' ORDER BY d.duplicate_count DESC, d.normalized_email ASC';

    $clustersStmt = $pdo->query($query);
    $clusterRows = $clustersStmt->fetchAll();
    if ($clusterRows === []) {
        return [];
    }

    $contactsStmt = $pdo->prepare('
        SELECT id, email, name, status, source, created_at, updated_at
        FROM mailing_contacts
        WHERE LOWER(TRIM(email)) = ?
        ORDER BY updated_at DESC, id DESC
    ');

    $clusters = [];
    foreach ($clusterRows as $clusterRow) {
        $normalizedEmail = strtolower(trim((string)$clusterRow['normalized_email']));
        if ($normalizedEmail === '') {
            continue;
        }

        $contactsStmt->execute([$normalizedEmail]);
        $contacts = $contactsStmt->fetchAll();
        foreach ($contacts as &$contact) {
            $contact['memberships'] = mailing_get_contact_memberships($pdo, (int)$contact['id']);
        }
        unset($contact);

        $clusters[] = [
            'normalized_email' => $normalizedEmail,
            'duplicate_count' => (int)$clusterRow['duplicate_count'],
            'is_ignored' => ((string)$clusterRow['ignored_email']) !== '',
            'ignored_by' => (string)$clusterRow['ignored_by'],
            'ignored_reason' => (string)$clusterRow['ignored_reason'],
            'ignored_at' => (string)$clusterRow['ignored_at'],
            'contacts' => $contacts,
        ];
    }

    return $clusters;
}

function mailing_ignore_duplicate_cluster(PDO $pdo, string $normalizedEmail, string $actor, string $reason = ''): void
{
    $normalizedEmail = normalize_mailing_email($normalizedEmail);
    if ($normalizedEmail === '') {
        throw new InvalidArgumentException('Normalized email is required.');
    }

    $stmt = $pdo->prepare('
        INSERT INTO mailing_duplicate_ignored_clusters (normalized_email, ignored_by, ignored_reason, ignored_at)
        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ON CONFLICT(normalized_email) DO UPDATE SET
            ignored_by = excluded.ignored_by,
            ignored_reason = excluded.ignored_reason,
            ignored_at = CURRENT_TIMESTAMP
    ');
    $stmt->execute([$normalizedEmail, trim($actor), trim($reason)]);
}

function mailing_unignore_duplicate_cluster(PDO $pdo, string $normalizedEmail): void
{
    $normalizedEmail = normalize_mailing_email($normalizedEmail);
    if ($normalizedEmail === '') {
        return;
    }

    $stmt = $pdo->prepare('DELETE FROM mailing_duplicate_ignored_clusters WHERE normalized_email = ?');
    $stmt->execute([$normalizedEmail]);
}

function mailing_contact_status_rank(string $status): int
{
    return match ($status) {
        'suppressed' => 4,
        'bounced' => 3,
        'unsubscribed' => 2,
        default => 1,
    };
}

function mailing_merge_contacts(PDO $pdo, int $winnerContactId, int $mergedContactId, string $actor): void
{
    if ($winnerContactId <= 0 || $mergedContactId <= 0 || $winnerContactId === $mergedContactId) {
        throw new InvalidArgumentException('Invalid merge parameters.');
    }

    $winner = mailing_get_contact_row($pdo, $winnerContactId);
    $merged = mailing_get_contact_row($pdo, $mergedContactId);
    if ($winner === null || $merged === null) {
        throw new RuntimeException('Unable to merge contacts that do not exist.');
    }

    $mergedMemberships = mailing_get_contact_memberships($pdo, $mergedContactId);
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $findWinnerMembership = $pdo->prepare('
            SELECT id, status, unsubscribed_at
            FROM mailing_list_memberships
            WHERE contact_id = ? AND list_id = ?
            LIMIT 1
        ');
        $insertWinnerMembership = $pdo->prepare('
            INSERT OR IGNORE INTO mailing_list_memberships (
                contact_id, list_id, status, source, subscribed_at, unsubscribed_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ');
        $updateWinnerMembership = $pdo->prepare('
            UPDATE mailing_list_memberships
            SET status = ?, unsubscribed_at = ?, source = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');

        foreach ($mergedMemberships as $membership) {
            $listId = (int)$membership['list_id'];
            if ($listId <= 0) {
                continue;
            }
            $mergedStatus = (string)$membership['status'];
            $mergedUnsubscribedAt = (string)($membership['unsubscribed_at'] ?? '');
            $mergedSubscribedAt = (string)($membership['subscribed_at'] ?? '');
            $mergedSource = trim((string)($membership['source'] ?? ''));
            if ($mergedSource === '') {
                $mergedSource = 'duplicate_merge';
            }

            $findWinnerMembership->execute([$winnerContactId, $listId]);
            $winnerMembership = $findWinnerMembership->fetch();
            if (!$winnerMembership) {
                $insertWinnerMembership->execute([
                    $winnerContactId,
                    $listId,
                    $mergedStatus === 'unsubscribed' ? 'unsubscribed' : 'active',
                    $mergedSource,
                    $mergedSubscribedAt !== '' ? $mergedSubscribedAt : gmdate('Y-m-d H:i:s'),
                    $mergedStatus === 'unsubscribed'
                        ? ($mergedUnsubscribedAt !== '' ? $mergedUnsubscribedAt : gmdate('Y-m-d H:i:s'))
                        : null,
                ]);
                continue;
            }

            $winnerStatus = (string)$winnerMembership['status'];
            if ($winnerStatus !== 'unsubscribed' && $mergedStatus === 'unsubscribed') {
                $updateWinnerMembership->execute([
                    'unsubscribed',
                    $mergedUnsubscribedAt !== '' ? $mergedUnsubscribedAt : gmdate('Y-m-d H:i:s'),
                    'duplicate_merge',
                    (int)$winnerMembership['id'],
                ]);
            }
        }

        if (trim((string)$winner['name']) === '' && trim((string)$merged['name']) !== '') {
            $updateWinnerName = $pdo->prepare('
                UPDATE mailing_contacts
                SET name = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            $updateWinnerName->execute([(string)$merged['name'], $winnerContactId]);
        }

        $winnerStatus = (string)$winner['status'];
        $mergedStatus = (string)$merged['status'];
        if (mailing_contact_status_rank($mergedStatus) > mailing_contact_status_rank($winnerStatus)) {
            $updateWinnerStatus = $pdo->prepare('
                UPDATE mailing_contacts
                SET status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            $updateWinnerStatus->execute([$mergedStatus, $winnerContactId]);
        }

        $payload = json_encode([
            'winner_before' => $winner,
            'merged_contact' => $merged,
            'merged_memberships' => $mergedMemberships,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            $payload = '';
        }

        $archiveStmt = $pdo->prepare('
            INSERT INTO mailing_contact_merge_archive (
                winner_contact_id,
                merged_contact_id,
                merged_contact_email,
                merged_contact_name,
                merged_contact_status,
                merged_contact_source,
                merged_payload,
                merged_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $archiveStmt->execute([
            $winnerContactId,
            $mergedContactId,
            (string)$merged['email'],
            (string)$merged['name'],
            (string)$merged['status'],
            (string)$merged['source'],
            $payload,
            trim($actor),
        ]);
        mailing_log_operation($pdo, 'merged_duplicate', $actor, [
            'contact_id' => $winnerContactId,
            'related_contact_id' => $mergedContactId,
            'details' => [
                'winner_email' => (string)$winner['email'],
                'merged_email' => (string)$merged['email'],
            ],
        ]);

        $deleteMemberships = $pdo->prepare('DELETE FROM mailing_list_memberships WHERE contact_id = ?');
        $deleteMemberships->execute([$mergedContactId]);
        $deleteContact = $pdo->prepare('DELETE FROM mailing_contacts WHERE id = ?');
        $deleteContact->execute([$mergedContactId]);

        mailing_unignore_duplicate_cluster($pdo, (string)$winner['email']);
        mailing_sync_contact_status($pdo, $winnerContactId);

        if ($ownsTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function get_mailing_recipients_by_list_slugs(PDO $pdo, array $listSlugs): array
{
    $slugs = normalize_mailing_list_slugs($listSlugs);
    if ($slugs === []) {
        return [];
    }

    $listMap = get_active_mailing_list_ids_by_slug($pdo);
    $listIds = [];
    foreach ($slugs as $slug) {
        $listId = $listMap[$slug] ?? 0;
        if ($listId > 0) {
            $listIds[$listId] = true;
        }
    }
    $selectedListIds = array_keys($listIds);
    if ($selectedListIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($selectedListIds), '?'));
    $stmt = $pdo->prepare("
        SELECT
            mc.id,
            mc.email,
            mc.name,
            mc.status,
            mc.updated_at,
            ml.slug AS list_slug
        FROM mailing_contacts mc
        INNER JOIN mailing_list_memberships mlm
            ON mlm.contact_id = mc.id
            AND mlm.status = 'active'
        INNER JOIN mailing_lists ml
            ON ml.id = mlm.list_id
            AND ml.is_active = 1
        WHERE mc.status = 'active'
          AND ml.id IN ({$placeholders})
        ORDER BY mc.updated_at DESC, mc.id DESC
    ");
    $stmt->execute($selectedListIds);
    $rows = $stmt->fetchAll();

    $resultByEmail = [];
    foreach ($rows as $row) {
        $email = normalize_mailing_email((string)($row['email'] ?? ''));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            continue;
        }
        if (!isset($resultByEmail[$email])) {
            $resultByEmail[$email] = [
                'contact_id' => (int)$row['id'],
                'email' => $email,
                'name' => (string)$row['name'],
                'list_slugs' => [],
            ];
        }
        $slug = strtolower(trim((string)($row['list_slug'] ?? '')));
        if ($slug !== '') {
            $resultByEmail[$email]['list_slugs'][$slug] = true;
        }
    }

    $recipients = [];
    foreach ($resultByEmail as $recipient) {
        $recipient['list_slugs'] = array_keys($recipient['list_slugs']);
        sort($recipient['list_slugs']);
        $recipients[] = $recipient;
    }

    return $recipients;
}

// ---------------------------------------------------------------------------
//  Tier helpers
// ---------------------------------------------------------------------------

/** Tier thresholds: review_count >= threshold → tier */
function get_tier_thresholds(): array
{
    return [
        4 => 10,  // Obsidian Chrononaut
        3 => 6,   // Golden Gearmaster
        2 => 3,   // Silver Steamwright
        1 => 1,   // Copper Cog
    ];
}

function get_tier_name(int $tier): string
{
    return match ($tier) {
        1 => 'Copper Cog Commendation',
        2 => 'Silver Steamwright Honors',
        3 => 'Golden Gearmaster Distinction',
        4 => 'Obsidian Chrononaut Medal of Honor',
        default => 'No Distinction Yet',
    };
}

function get_tier_short(int $tier): string
{
    return match ($tier) {
        1 => 'Copper Cog',
        2 => 'Silver Steamwright',
        3 => 'Golden Gearmaster',
        4 => 'Obsidian Chrononaut',
        default => 'Unranked',
    };
}

function get_tier_css_class(int $tier): string
{
    return match ($tier) {
        1 => 'copper',
        2 => 'silver',
        3 => 'gold',
        4 => 'obsidian',
        default => 'none',
    };
}

/**
 * Check if a member should be promoted and apply the promotion.
 * Returns the new tier if promoted, or 0 if no change.
 */
function check_tier_promotion(PDO $pdo, int $member_id): int
{
    $stmt = $pdo->prepare('SELECT tier, review_count FROM members WHERE id = ?');
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();

    if (!$member) {
        return 0;
    }

    $current_tier  = (int)$member['tier'];
    $review_count  = (int)$member['review_count'];
    $new_tier      = 0;

    foreach (get_tier_thresholds() as $tier => $threshold) {
        if ($review_count >= $threshold) {
            $new_tier = $tier;
            break;
        }
    }

    if ($new_tier > $current_tier) {
        $pdo->prepare('UPDATE members SET tier = ? WHERE id = ?')->execute([$new_tier, $member_id]);
        $pdo->prepare('INSERT INTO distinctions (member_id, tier) VALUES (?, ?)')->execute([$member_id, $new_tier]);
        return $new_tier;
    }

    return 0;
}

/**
 * Get the next tier info for progress display.
 * Returns ['next_tier' => int, 'reviews_needed' => int, 'progress_pct' => float] or null if max tier.
 */
function get_tier_progress(int $current_tier, int $review_count): ?array
{
    $thresholds = get_tier_thresholds();

    $next_tier = $current_tier + 1;
    if ($next_tier > 4) {
        return null; // Already at max
    }

    $next_threshold = $thresholds[$next_tier];
    $prev_threshold = $current_tier > 0 ? $thresholds[$current_tier] : 0;
    $range          = $next_threshold - $prev_threshold;
    $progress       = $review_count - $prev_threshold;
    $pct            = $range > 0 ? min(100, max(0, ($progress / $range) * 100)) : 0;

    return [
        'next_tier'      => $next_tier,
        'reviews_needed' => max(0, $next_threshold - $review_count),
        'progress_pct'   => round($pct, 1),
    ];
}
