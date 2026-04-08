<?php
declare(strict_types=1);

/**
 * Book Catalog — Centralized data for all books and series.
 *
 * To add a new book:
 *   1. Add an entry to $fiction_books or $nonfiction_books below.
 *   2. Create a page file in fiction/ or non-fiction/ that sets $book_slug
 *      and includes the corresponding template.
 *
 * To add a new series:
 *   1. Add an entry to $series_catalog below.
 *   2. Create a page file in series/ that sets $series_slug
 *      and includes the series template.
 */

// ---------------------------------------------------------------------------
//  Fiction Books
// ---------------------------------------------------------------------------
$fiction_books = [

    'michael-strogoff' => [
        'title'          => 'Jules Verne's Michael Strogoff, or The Courier of the Czar',
        'short_title'    => 'Michael Strogoff',
        'slug'           => 'michael-strogoff',
        'cover'          => '/assets/images/books/michael-strogoff.jpg',
        'hook'           => 'A gripping two-book adventure novel set in 19th-century Russia during a Tartar rebellion against the Czar.',
        'synopsis'       => 'The story follows Michael Strogoff, a brave and resourceful courier entrusted with a vital mission: to deliver an urgent message to the governor of Irkutsk and warn him of a treacherous plot led by the cunning traitor Ivan Ogareff. Traveling over 5,000 miles through the harsh Siberian landscape, Michael faces relentless dangers, including enemy spies, brutal Tartar warriors, and the ever-present threat of exposure. Combining elements of historical fiction, adventure, and espionage, Verne's novel is a testament to courage, duty, and resilience.',
        'excerpt'        => '', // Add an excerpt passage when ready
        'series_slug'    => 'steampunk-chronicles',
        'series_order'   => 1,
        'tags'           => ['steampunk', 'adventure', 'science-fiction', 'historical'],
        'buy_links'      => [
            ['platform' => 'Amazon',          'url' => '#'],
            ['platform' => 'Barnes & Noble',  'url' => '#'],
            ['platform' => 'Goodreads',       'url' => '#'],
        ],
        'reviews'        => [
            // ['text' => 'Review text here.', 'reviewer' => 'Reviewer Name', 'source' => 'Amazon'],
        ],
        'related'        => [], // slugs of related fiction books
        'status'         => 'published', // published | coming-soon
        'published_date' => '', // YYYY-MM-DD when known
    ],

    // Add more fiction books here following the same structure.
];

// ---------------------------------------------------------------------------
//  Non-Fiction Books
// ---------------------------------------------------------------------------
$nonfiction_books = [

    'sample-nonfiction' => [
        'title'          => 'Sample Non-Fiction Title',
        'short_title'    => 'Sample Non-Fiction',
        'slug'           => 'sample-nonfiction',
        'cover'          => '/assets/images/books/sample-nonfiction.jpg',
        'hook'           => 'A compelling exploration of real-world ideas.',
        'premise'        => 'This book examines the intersection of technology, creativity, and human potential through a practical and accessible lens.',
        'what_readers_learn' => [
            'How to apply creative thinking to real-world problems',
            'The principles behind resilient systems and communities',
            'Actionable strategies for personal and professional growth',
        ],
        'key_themes'     => ['creativity', 'technology', 'resilience', 'growth'],
        'excerpt'        => '', // Add a sample chapter or passage when ready
        'tags'           => ['non-fiction', 'self-improvement', 'technology'],
        'buy_links'      => [
            ['platform' => 'Amazon',          'url' => '#'],
            ['platform' => 'Barnes & Noble',  'url' => '#'],
            ['platform' => 'Goodreads',       'url' => '#'],
        ],
        'endorsements'   => [
            // ['text' => 'Endorsement text.', 'name' => 'Endorser Name', 'title' => 'Title / Affiliation'],
        ],
        'related'        => [], // slugs of related non-fiction books
        'status'         => 'coming-soon',
        'published_date' => '',
    ],

    // Add more non-fiction books here following the same structure.
];

// ---------------------------------------------------------------------------
//  Series Catalog
// ---------------------------------------------------------------------------
$series_catalog = [

    'steampunk-chronicles' => [
        'name'        => 'The Steampunk Chronicles',
        'slug'        => 'steampunk-chronicles',
        'cover'       => '/assets/images/series/steampunk-chronicles.jpg',
        'description' => 'An epic eight-part steampunk science fiction novella series tracing the evolution of steampunk technology — from the age of steam, coal, and wood to futuristic advancements in space travel.',
        'books'       => ['michael-strogoff'], // fiction slugs in reading order
        'status'      => 'in-progress', // complete | in-progress
    ],

    // Add more series here.
];

// ---------------------------------------------------------------------------
//  Catalog Helper Functions
// ---------------------------------------------------------------------------

/**
 * Get a fiction book by slug, or null if not found.
 */
function get_fiction_book(string $slug): ?array {
    global $fiction_books;
    return $fiction_books[$slug] ?? null;
}

/**
 * Get a non-fiction book by slug, or null if not found.
 */
function get_nonfiction_book(string $slug): ?array {
    global $nonfiction_books;
    return $nonfiction_books[$slug] ?? null;
}

/**
 * Get a series by slug, or null if not found.
 */
function get_series(string $slug): ?array {
    global $series_catalog;
    return $series_catalog[$slug] ?? null;
}

/**
 * Get the series name for a fiction book, or empty string.
 */
function get_book_series_name(array $book): string {
    if (empty($book['series_slug'])) {
        return '';
    }
    $series = get_series($book['series_slug']);
    return $series['name'] ?? '';
}

/**
 * Get all fiction books filtered by status. Pass null for all.
 */
function get_fiction_books(?string $status = null): array {
    global $fiction_books;
    if ($status === null) {
        return $fiction_books;
    }
    return array_filter($fiction_books, fn(array $b) => $b['status'] === $status);
}

/**
 * Get all non-fiction books filtered by status. Pass null for all.
 */
function get_nonfiction_books(?string $status = null): array {
    global $nonfiction_books;
    if ($status === null) {
        return $nonfiction_books;
    }
    return array_filter($nonfiction_books, fn(array $b) => $b['status'] === $status);
}

/**
 * Get all books in a series, in reading order.
 */
function get_series_books(string $series_slug): array {
    $series = get_series($series_slug);
    if ($series === null) {
        return [];
    }
    $books = [];
    foreach ($series['books'] as $slug) {
        $book = get_fiction_book($slug);
        if ($book !== null) {
            $books[] = $book;
        }
    }
    return $books;
}

/**
 * Get related books for a given book (fiction or non-fiction).
 * Returns an array of book data arrays.
 */
function get_related_books(array $book, string $type = 'fiction'): array {
    $related = [];
    foreach (($book['related'] ?? []) as $slug) {
        $found = $type === 'fiction' ? get_fiction_book($slug) : get_nonfiction_book($slug);
        if ($found !== null) {
            $related[] = $found;
        }
    }
    return $related;
}
