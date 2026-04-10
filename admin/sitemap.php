<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/book-catalog.php';

$message = '';
$base = 'https://authorjuanjose.io';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    $urls = [
        ['loc' => '/',              'priority' => '1.0', 'changefreq' => 'weekly'],
        ['loc' => '/fiction',       'priority' => '0.9', 'changefreq' => 'weekly'],
        ['loc' => '/non-fiction',   'priority' => '0.9', 'changefreq' => 'weekly'],
        ['loc' => '/about',         'priority' => '0.8', 'changefreq' => 'monthly'],
        ['loc' => '/journal',       'priority' => '0.7', 'changefreq' => 'weekly'],
        ['loc' => '/media',         'priority' => '0.6', 'changefreq' => 'monthly'],
        ['loc' => '/events',        'priority' => '0.6', 'changefreq' => 'monthly'],
        ['loc' => '/contact',       'priority' => '0.5', 'changefreq' => 'monthly'],
        ['loc' => '/library',       'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => '/gallery',       'priority' => '0.7', 'changefreq' => 'weekly'],
        ['loc' => '/tags',          'priority' => '0.6', 'changefreq' => 'weekly'],
        ['loc' => '/start-here',    'priority' => '0.7', 'changefreq' => 'monthly'],
        ['loc' => '/search',        'priority' => '0.4', 'changefreq' => 'monthly'],
        ['loc' => '/arc-reader-club',           'priority' => '0.8', 'changefreq' => 'monthly'],
        ['loc' => '/arc-reader-club/join',      'priority' => '0.7', 'changefreq' => 'monthly'],
        ['loc' => '/arc-reader-club/how-it-works', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['loc' => '/arc-reader-club/honors-and-distinctions', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['loc' => '/arc-reader-club/faq',       'priority' => '0.5', 'changefreq' => 'monthly'],
        ['loc' => '/privacy',       'priority' => '0.3', 'changefreq' => 'yearly'],
    ];

    // Add fiction books
    foreach (get_fiction_books() as $slug => $book) {
        $urls[] = ['loc' => '/fiction/' . $slug, 'priority' => '0.8', 'changefreq' => 'monthly'];
    }

    // Add non-fiction books
    foreach (get_nonfiction_books() as $slug => $book) {
        $urls[] = ['loc' => '/non-fiction/' . $slug, 'priority' => '0.8', 'changefreq' => 'monthly'];
    }

    // Add series
    global $series_catalog;
    foreach ($series_catalog as $slug => $s) {
        $urls[] = ['loc' => '/series/' . $slug, 'priority' => '0.7', 'changefreq' => 'monthly'];
    }

    // Build XML
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    $today = date('Y-m-d');
    foreach ($urls as $u) {
        $xml .= '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . htmlspecialchars($base . $u['loc']) . '</loc>' . PHP_EOL;
        $xml .= '    <lastmod>' . $today . '</lastmod>' . PHP_EOL;
        $xml .= '    <changefreq>' . $u['changefreq'] . '</changefreq>' . PHP_EOL;
        $xml .= '    <priority>' . $u['priority'] . '</priority>' . PHP_EOL;
        $xml .= '  </url>' . PHP_EOL;
    }
    $xml .= '</urlset>' . PHP_EOL;

    $path = dirname(__DIR__) . '/sitemap.xml';
    file_put_contents($path, $xml);
    $message = 'Sitemap generated with ' . count($urls) . ' URLs.';
}

$sitemap_exists = file_exists(dirname(__DIR__) . '/sitemap.xml');

$page_title = 'Sitemap Generator | Admin';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Sitemap Generator</h1>
  <p><a href="/admin">&larr; Admin Dashboard</a></p>

  <?php if ($message !== ''): ?>
    <div class="alert alert--success" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <div class="panel">
    <p>Status: <?php echo $sitemap_exists ? '<strong style="color:var(--success)">sitemap.xml exists</strong>' : '<strong style="color:var(--danger)">sitemap.xml not found</strong>'; ?></p>
    <form method="post">
      <button class="button button--lg" type="submit" name="action" value="generate">Generate Sitemap</button>
    </form>
  </div>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
