<?php
declare(strict_types=1);

/**
 * Series Page Template
 *
 * Usage — create a file in series/ like this:
 *
 *   <?php
 *   declare(strict_types=1);
 *   $series_slug = 'steampunk-chronicles';
 *   require_once dirname(__DIR__) . '/includes/templates/series-page.php';
 *
 * The template loads the series from the catalog and renders the full page.
 */

$project_root = dirname(__DIR__, 2);

require_once $project_root . '/includes/book-catalog.php';

// ---------------------------------------------------------------------------
//  Load series data
// ---------------------------------------------------------------------------
$series_slug = $series_slug ?? '';
$series = get_series($series_slug);

if ($series === null) {
    http_response_code(404);
    $page_title = 'Series Not Found | AuthorJuanJose.io';
    require_once $project_root . '/includes/header.php';
    echo '<main class="container page-shell"><section class="panel"><h1>Series Not Found</h1><p>The series you are looking for does not exist. <a href="/fiction">Browse fiction</a>.</p></section></main>';
    require_once $project_root . '/includes/footer.php';
    return;
}

$page_title = htmlspecialchars($series['name'], ENT_QUOTES, 'UTF-8') . ' | AuthorJuanJose.io';
$books = get_series_books($series_slug);

require_once $project_root . '/includes/header.php';
?>

<!-- Series Hero -->
<section class="hero">
  <div class="container">
    <p class="section-label" style="color:rgba(255,255,255,.7)">Book Series</p>
    <h1><?php echo htmlspecialchars($series['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="lead"><?php echo htmlspecialchars($series['description'], ENT_QUOTES, 'UTF-8'); ?></p>
    <?php if ($series['status'] === 'in-progress'): ?>
      <p style="color:rgba(255,255,255,.7);font-style:italic">Series in progress &mdash; more titles coming soon.</p>
    <?php endif; ?>
  </div>
</section>

<main class="container page-shell">

  <!-- Reading Order -->
  <?php if (count($books) > 0): ?>
    <section class="section">
      <p class="section-label">Recommended Reading Order</p>
      <h2>The Books</h2>
      <div class="series-book-list">
        <?php foreach ($books as $index => $book): ?>
          <a class="series-book-item" href="/fiction/<?php echo htmlspecialchars($book['slug'], ENT_QUOTES, 'UTF-8'); ?>">
            <span class="series-book-item__number"><?php echo $index + 1; ?></span>
            <?php if (!empty($book['cover'])): ?>
              <div class="series-book-item__cover">
                <img src="<?php echo htmlspecialchars($book['cover'], ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo htmlspecialchars($book['short_title'] ?? $book['title'], ENT_QUOTES, 'UTF-8'); ?> cover"
                     loading="lazy">
              </div>
            <?php endif; ?>
            <div class="series-book-item__info">
              <h3><?php echo htmlspecialchars($book['short_title'] ?? $book['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p><?php echo htmlspecialchars($book['hook'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
              <?php if ($book['status'] === 'coming-soon'): ?>
                <span class="book-badge book-badge--coming-soon">Coming Soon</span>
              <?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php else: ?>
    <section class="section">
      <p>Books in this series will be listed here as they are announced.</p>
    </section>
  <?php endif; ?>

  <!-- CTA -->
  <div class="divider-gear"></div>
  <section class="section text-center">
    <h2>Follow the Journey</h2>
    <p class="lead" style="margin:0 auto var(--space-lg)">Be the first to know when new books in this series are released.</p>
    <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
    <a class="button button--outline button--lg" href="/fiction" style="margin-left:.5rem">Browse All Fiction</a>
  </section>

</main>
<?php require_once $project_root . '/includes/footer.php'; ?>
