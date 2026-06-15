<?php
declare(strict_types=1);

/**
 * Journal storage helpers.
 *
 * Entries are stored in data/journal-entries.json.
 */
function ajj_journal_storage_path(): string
{
    return dirname(__DIR__) . '/data/journal-entries.json';
}

/**
 * Default categories available in the journal editor.
 *
 * @return string[]
 */
function ajj_journal_categories(): array
{
    return [
        'Fiction',
        'Non-Fiction',
        'Behind the Scenes',
    ];
}

/**
 * Seed entries used when no journal storage file exists yet.
 *
 * @return array<int, array<string, string>>
 */
function ajj_journal_default_entries(): array
{
    return [
        [
            'title' => 'Welcome to the Journal',
            'date' => '2026-04-09',
            'category' => 'Behind the Scenes',
            'excerpt' => 'Writing updates, behind-the-book notes, essays, and reflections all live here.',
            'body' => "Welcome to the Author Juan Jose Journal.\n\nThis is the space for writing updates, behind-the-scenes notes on upcoming releases, reflections on the creative process, and occasional deep dives into the worlds being built.",
            'slug' => 'welcome-to-the-journal',
        ],
    ];
}

function ajj_journal_make_excerpt(string $body, int $maxLength = 180): string
{
    $clean = trim(preg_replace('/\s+/', ' ', $body) ?? '');
    if ($clean === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($clean) <= $maxLength) {
            return $clean;
        }
        return rtrim(mb_substr($clean, 0, $maxLength - 3)) . '...';
    }

    if (strlen($clean) <= $maxLength) {
        return $clean;
    }

    return rtrim(substr($clean, 0, $maxLength - 3)) . '...';
}

/**
 * @param string[] $existingSlugs
 */
function ajj_journal_generate_unique_slug(string $title, array $existingSlugs = []): string
{
    $base = strtolower(trim($title));
    $base = preg_replace('/[^a-z0-9]+/i', '-', $base) ?? '';
    $base = trim($base, '-');

    if ($base === '') {
        $base = 'journal-entry-' . date('YmdHis');
    }

    $slug = $base;
    $suffix = 2;
    while (in_array($slug, $existingSlugs, true)) {
        $slug = $base . '-' . $suffix;
        $suffix++;
    }

    return $slug;
}

/**
 * @param array<string, mixed> $entry
 * @param string[] $existingSlugs
 * @return array<string, string>|null
 */
function ajj_journal_normalize_entry(array $entry, array &$existingSlugs): ?array
{
    $title = trim((string)($entry['title'] ?? ''));
    $body = str_replace(["\r\n", "\r"], "\n", trim((string)($entry['body'] ?? '')));

    if ($title === '' || $body === '') {
        return null;
    }

    $date = trim((string)($entry['date'] ?? ''));
    $dateObj = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    if ($dateObj === false || $dateObj->format('Y-m-d') !== $date) {
        $date = date('Y-m-d');
    }

    $category = trim((string)($entry['category'] ?? 'Behind the Scenes'));
    if ($category === '') {
        $category = 'Behind the Scenes';
    }

    $excerpt = trim((string)($entry['excerpt'] ?? ''));
    if ($excerpt === '') {
        $excerpt = ajj_journal_make_excerpt($body);
    }

    $rawSlug = trim((string)($entry['slug'] ?? ''));
    $slugSource = $rawSlug !== '' ? $rawSlug : $title;
    $slug = ajj_journal_generate_unique_slug($slugSource, $existingSlugs);
    $existingSlugs[] = $slug;

    return [
        'title' => $title,
        'date' => $date,
        'category' => $category,
        'excerpt' => $excerpt,
        'body' => $body,
        'slug' => $slug,
    ];
}

/**
 * @return array<int, array<string, string>>
 */
function ajj_load_journal_entries(): array
{
    $path = ajj_journal_storage_path();
    $fileExists = is_file($path);
    $rawEntries = [];
    $fallbackToDefault = false;

    if (!$fileExists) {
        $rawEntries = ajj_journal_default_entries();
    } else {
        $json = file_get_contents($path);
        if ($json === false) {
            $fallbackToDefault = true;
            $rawEntries = ajj_journal_default_entries();
        } else {
            $trimmed = trim($json);
            if ($trimmed === '') {
                $rawEntries = [];
            } else {
                try {
                    $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
                    if (!is_array($decoded)) {
                        $fallbackToDefault = true;
                        $rawEntries = ajj_journal_default_entries();
                    } else {
                        $rawEntries = $decoded;
                    }
                } catch (Throwable $e) {
                    $fallbackToDefault = true;
                    $rawEntries = ajj_journal_default_entries();
                }
            }
        }
    }

    $normalized = [];
    $usedSlugs = [];
    foreach ($rawEntries as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $clean = ajj_journal_normalize_entry($entry, $usedSlugs);
        if ($clean !== null) {
            $normalized[] = $clean;
        }
    }

    if ($normalized === [] && $fallbackToDefault) {
        $defaults = ajj_journal_default_entries();
        foreach ($defaults as $entry) {
            $clean = ajj_journal_normalize_entry($entry, $usedSlugs);
            if ($clean !== null) {
                $normalized[] = $clean;
            }
        }
    }

    return $normalized;
}

/**
 * @param array<int, array<string, string>> $entries
 */
function ajj_save_journal_entries(array $entries): bool
{
    $path = ajj_journal_storage_path();
    $dir = dirname($path);

    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }

    $normalized = [];
    $usedSlugs = [];
    foreach ($entries as $entry) {
        $clean = ajj_journal_normalize_entry($entry, $usedSlugs);
        if ($clean !== null) {
            $normalized[] = $clean;
        }
    }

    $json = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        return false;
    }

    return file_put_contents($path, $json . PHP_EOL, LOCK_EX) !== false;
}
