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
