<?php
declare(strict_types=1);

/**
 * Fiction Book Page Template
 *
 * Usage — create a file in fiction/ like this:
 *
 *   <?php
 *   declare(strict_types=1);
 *   $book_slug = 'michael-strogoff';
 *   require_once dirname(__DIR__) . '/includes/templates/book-fiction.php';
 *
 * The template loads the book from the catalog, sets the page title,
 * and renders the full book detail page.
 */

// Resolve paths relative to the project root
$project_root = dirname(__DIR__, 2);

require_once $project_root . '/includes/book-catalog.php';

// ---------------------------------------------------------------------------
//  Load book data
// ---------------------------------------------------------------------------
$book_slug = $book_slug ?? '';
$book = get_fiction_book($book_slug);

if ($book === null) {
    http_response_code(404);
    $page_title = 'Book Not Found | AuthorJuanJose.io';
    require_once $project_root . '/includes/header.php';
    echo '<main class="container page-shell"><section class="panel"><h1>Book Not Found</h1><p>The book you are looking for does not exist. <a href="/fiction">Browse all fiction</a>.</p></section></main>';
    require_once $project_root . '/includes/footer.php';
    return;
}

$page_title = htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') . ' | AuthorJuanJose.io';
$page_description = $book['hook'] ?? $book['title'];
$page_og_image = $book['cover'] ?? '/assets/images/og-default.jpg';
$series_name = get_book_series_name($book);

require_once $project_root . '/includes/header.php';
?>

<main class="container page-shell">

  <!-- Book Detail Header -->
  <section class="book-detail">
    <?php if (!empty($book['cover'])): ?>
      <div class="book-detail__cover">
        <img src="<?php echo htmlspecialchars($book['cover'], ENT_QUOTES, 'UTF-8'); ?>"
             alt="<?php echo htmlspecialchars($book['short_title'] ?? $book['title'], ENT_QUOTES, 'UTF-8'); ?> cover">
      </div>
    <?php endif; ?>

    <div class="book-detail__info">
      <?php if ($series_name !== ''): ?>
        <p class="section-label">
          <a href="/series/<?php echo htmlspecialchars($book['series_slug'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($series_name, ENT_QUOTES, 'UTF-8'); ?>
            <?php if (!empty($book['series_order'])): ?>
              &mdash; Book <?php echo (int)$book['series_order']; ?>
            <?php endif; ?>
          </a>
        </p>
      <?php endif; ?>

      <h1><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></h1>

      <?php if (!empty($book['hook'])): ?>
        <p class="lead"><?php echo htmlspecialchars($book['hook'], ENT_QUOTES, 'UTF-8'); ?></p>
      <?php endif; ?>

      <?php if (!empty($book['tags'])): ?>
        <div class="tag-list">
          <?php foreach ($book['tags'] as $tag): ?>
            <a class="tag" href="/tags/view?tag=<?php echo urlencode(strtolower($tag)); ?>"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($book['status'] === 'coming-soon'): ?>
        <p class="book-badge book-badge--coming-soon">Coming Soon</p>
      <?php endif; ?>

      <?php
      // Buy links
      $buy_links = $book['buy_links'] ?? [];
      include $project_root . '/includes/components/buy-links.php';
      ?>
    </div>
  </section>

  <div class="divider-gear"></div>

  <!-- Synopsis -->
  <?php if (!empty($book['synopsis'])): ?>
    <section class="section">
      <p class="section-label">The Story</p>
      <h2>Synopsis</h2>
      <div class="book-prose">
        <?php echo '<p>' . nl2br(htmlspecialchars($book['synopsis'], ENT_QUOTES, 'UTF-8')) . '</p>'; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Excerpt -->
  <?php if (!empty($book['excerpt'])): ?>
    <div class="divider-gear"></div>
    <section class="section">
      <p class="section-label">Read a Passage</p>
      <h2>Excerpt</h2>
      <blockquote class="book-excerpt">
        <?php echo nl2br(htmlspecialchars($book['excerpt'], ENT_QUOTES, 'UTF-8')); ?>
      </blockquote>
    </section>
  <?php endif; ?>

  <!-- Reviews -->
  <?php if (!empty($book['reviews'])): ?>
    <div class="divider-gear"></div>
    <section class="section">
      <p class="section-label">What Readers Say</p>
      <h2>Reviews</h2>
      <div class="review-grid">
        <?php foreach ($book['reviews'] as $review): ?>
          <div class="review-card">
            <blockquote>
              <p>&ldquo;<?php echo htmlspecialchars($review['text'], ENT_QUOTES, 'UTF-8'); ?>&rdquo;</p>
            </blockquote>
            <cite>
              &mdash; <?php echo htmlspecialchars($review['reviewer'], ENT_QUOTES, 'UTF-8'); ?>
              <?php if (!empty($review['source'])): ?>
                <span class="review-source"><?php echo htmlspecialchars($review['source'], ENT_QUOTES, 'UTF-8'); ?></span>
              <?php endif; ?>
            </cite>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Series Reading Order -->
  <?php if ($series_name !== ''): ?>
    <?php $series_books = get_series_books($book['series_slug']); ?>
    <?php if (count($series_books) > 1): ?>
      <div class="divider-gear"></div>
      <section class="section">
        <p class="section-label"><?php echo htmlspecialchars($series_name, ENT_QUOTES, 'UTF-8'); ?></p>
        <h2>Reading Order</h2>
        <ol class="reading-order">
          <?php foreach ($series_books as $sb): ?>
            <li class="<?php echo $sb['slug'] === $book_slug ? 'is-current' : ''; ?>">
              <?php if ($sb['slug'] === $book_slug): ?>
                <strong><?php echo htmlspecialchars($sb['short_title'] ?? $sb['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <span class="reading-order__badge">You are here</span>
              <?php else: ?>
                <a href="/fiction/<?php echo htmlspecialchars($sb['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo htmlspecialchars($sb['short_title'] ?? $sb['title'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
        <p><a class="button button--outline" href="/series/<?php echo htmlspecialchars($book['series_slug'], ENT_QUOTES, 'UTF-8'); ?>">View Full Series</a></p>
      </section>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Related Books -->
  <?php
  $related_books = get_related_books($book, 'fiction');
  $related_type = 'fiction';
  include $project_root . '/includes/components/related-books.php';
  ?>

  <!-- Cross-Category Suggestions -->
  <?php $cross = get_cross_category_suggestions($book, 'fiction'); ?>
  <?php if (count($cross) > 0): ?>
    <div class="divider-gear"></div>
    <section class="section">
      <p class="section-label">From the Other Side</p>
      <h2>Non-Fiction You Might Enjoy</h2>
      <div class="card-grid">
        <?php foreach ($cross as $cb): ?>
          <a class="book-card" href="/non-fiction/<?php echo htmlspecialchars($cb['slug'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php if (!empty($cb['cover'])): ?>
              <div class="book-card__cover">
                <img src="<?php echo htmlspecialchars($cb['cover'], ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo htmlspecialchars($cb['short_title'] ?? $cb['title'], ENT_QUOTES, 'UTF-8'); ?>"
                     loading="lazy">
              </div>
            <?php endif; ?>
            <div class="book-card__info">
              <span class="book-badge book-badge--nonfiction">Non-Fiction</span>
              <h3><?php echo htmlspecialchars($cb['short_title'] ?? $cb['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p><?php echo htmlspecialchars($cb['hook'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Newsletter CTA -->
  <div class="divider-gear"></div>
  <section class="section text-center">
    <h2>Stay in the World</h2>
    <p class="lead" style="margin:0 auto var(--space-lg)">Get updates on new releases, behind-the-scenes content, and upcoming steampunk adventures.</p>
    <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
  </section>

</main>
<?php require_once $project_root . '/includes/footer.php'; ?>
