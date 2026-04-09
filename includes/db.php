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
