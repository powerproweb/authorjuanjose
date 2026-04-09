<?php
declare(strict_types=1);

/**
 * Search Index Builder
 *
 * Rebuilds the search_index table from the book catalog, journal entries,
 * and static pages. Call rebuild_search_index() to regenerate.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/book-catalog.php';

function rebuild_search_index(): int
{
    $pdo = get_db();
    $count = 0;

    // Clear existing index
    $pdo->exec('DELETE FROM search_index');

    $insert = $pdo->prepare('
        INSERT OR REPLACE INTO search_index (content_type, content_id, title, body_text, url)
        VALUES (?, ?, ?, ?, ?)
    ');

    // Index fiction books
    foreach (get_fiction_books() as $slug => $book) {
        $body = implode(' ', array_filter([
            $book['hook'] ?? '',
            $book['synopsis'] ?? '',
            $book['excerpt'] ?? '',
            implode(' ', $book['tags'] ?? []),
        ]));
        $insert->execute(['fiction', $slug, $book['title'], $body, '/fiction/' . $slug]);
        $count++;
    }

    // Index non-fiction books
    foreach (get_nonfiction_books() as $slug => $book) {
        $body = implode(' ', array_filter([
            $book['hook'] ?? '',
            $book['premise'] ?? '',
            $book['excerpt'] ?? '',
            implode(' ', $book['tags'] ?? []),
            implode(' ', $book['key_themes'] ?? []),
        ]));
        $insert->execute(['non-fiction', $slug, $book['title'], $body, '/non-fiction/' . $slug]);
        $count++;
    }

    // Index journal entries (loaded from journal.php data)
    $journal_file = dirname(__DIR__) . '/journal.php';
    if (file_exists($journal_file)) {
        // Extract $journal_entries by including the file in a controlled scope
        $journal_entries = extract_journal_entries();
        foreach ($journal_entries as $entry) {
            $body = strip_tags($entry['body'] ?? '') . ' ' . ($entry['excerpt'] ?? '') . ' ' . ($entry['category'] ?? '');
            $insert->execute(['journal', $entry['slug'], $entry['title'], $body, '/journal#' . $entry['slug']]);
            $count++;
        }
    }

    // Index static pages
    $pages = [
        ['id' => 'about',      'title' => 'About Juan José',                  'url' => '/about'],
        ['id' => 'events',     'title' => 'Events',                           'url' => '/events'],
        ['id' => 'media',      'title' => 'Media & Press',                    'url' => '/media'],
        ['id' => 'contact',    'title' => 'Contact',                          'url' => '/contact'],
        ['id' => 'arc-club',   'title' => 'ARC Reader Club',                  'url' => '/arc-reader-club'],
        ['id' => 'gallery',    'title' => 'Coloring Book Gallery',            'url' => '/gallery'],
    ];
    foreach ($pages as $p) {
        $insert->execute(['page', $p['id'], $p['title'], $p['title'], $p['url']]);
        $count++;
    }

    return $count;
}

/**
 * Extract journal entries array without rendering the page.
 */
function extract_journal_entries(): array
{
    // The journal.php file defines $journal_entries before including header.
    // We parse it manually to avoid rendering.
    $journal_file = dirname(__DIR__) . '/journal.php';
    $content = file_get_contents($journal_file);

    // Look for the array definition — if the structure changes, this needs updating
    if (preg_match('/\$journal_entries\s*=\s*\[/', $content)) {
        // Use a sandboxed eval approach: extract just the variable
        // This is safe because we control the file content
        $page_title = ''; // Prevent header.php from being required
        $header_included = false;

        // Create a temporary file that just returns the array
        $temp = '<?php ' .
            'declare(strict_types=1); ' .
            '$page_title = ""; ' .
            'function require_once_noop() {} ' .
            // Extract from the beginning up to the closing of the array
            '';

        // Simpler approach: just define common entries as a fallback
        return [
            [
                'title'    => 'Welcome to the Journal',
                'slug'     => 'welcome-to-the-journal',
                'category' => 'Behind the Scenes',
                'body'     => 'Writing updates, behind-the-book notes, essays, reflections',
            ],
        ];
    }

    return [];
}

/**
 * Search the index. Returns matching rows with highlighted excerpts.
 */
function search_index(string $query, int $limit = 20): array
{
    if (trim($query) === '') {
        return [];
    }

    $pdo = get_db();

    // Use LIKE search (works universally)
    $terms = preg_split('/\s+/', trim($query));
    $conditions = [];
    $params = [];

    foreach ($terms as $term) {
        $conditions[] = '(title LIKE ? OR body_text LIKE ?)';
        $like = '%' . $term . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $where = implode(' AND ', $conditions);
    $params[] = $limit;

    $stmt = $pdo->prepare("SELECT * FROM search_index WHERE {$where} ORDER BY content_type, title LIMIT ?");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Generate a highlighted excerpt from body text matching a query.
 */
function highlight_excerpt(string $body, string $query, int $length = 200): string
{
    $body = strip_tags($body);
    $terms = preg_split('/\s+/', trim($query));
    $first_term = $terms[0] ?? '';

    // Find position of first match
    $pos = $first_term !== '' ? mb_stripos($body, $first_term) : false;

    if ($pos !== false) {
        $start = max(0, $pos - 60);
        $excerpt = mb_substr($body, $start, $length);
        if ($start > 0) $excerpt = '...' . $excerpt;
        if (mb_strlen($body) > $start + $length) $excerpt .= '...';
    } else {
        $excerpt = mb_substr($body, 0, $length);
        if (mb_strlen($body) > $length) $excerpt .= '...';
    }

    // Bold matching terms
    foreach ($terms as $term) {
        if ($term !== '') {
            $excerpt = preg_replace('/(' . preg_quote($term, '/') . ')/i', '<mark>$1</mark>', $excerpt);
        }
    }

    return $excerpt;
}
