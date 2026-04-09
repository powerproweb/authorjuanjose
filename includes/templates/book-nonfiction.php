<?php
declare(strict_types=1);

/**
 * Non-Fiction Book Page Template
 *
 * Usage — create a file in non-fiction/ like this:
 *
 *   <?php
 *   declare(strict_types=1);
 *   $book_slug = 'sample-nonfiction';
 *   require_once dirname(__DIR__) . '/includes/templates/book-nonfiction.php';
 *
 * The template loads the book from the catalog, sets the page title,
 * and renders the full book detail page.
 */

$project_root = dirname(__DIR__, 2);

require_once $project_root . '/includes/book-catalog.php';

// ---------------------------------------------------------------------------
//  Load book data
// ---------------------------------------------------------------------------
$book_slug = $book_slug ?? '';
$book = get_nonfiction_book($book_slug);

if ($book === null) {
    http_response_code(404);
    $page_title = 'Book Not Found | AuthorJuanJose.io';
    require_once $project_root . '/includes/header.php';
    echo '<main class="container page-shell"><section class="panel"><h1>Book Not Found</h1><p>The book you are looking for does not exist. <a href="/non-fiction">Browse all non-fiction</a>.</p></section></main>';
    require_once $project_root . '/includes/footer.php';
    return;
}

$page_title = htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') . ' | AuthorJuanJose.io';

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
      <p class="section-label">Non-Fiction</p>
      <h1><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></h1>

      <?php if (!empty($book['hook'])): ?>
        <p class="lead"><?php echo htmlspecialchars($book['hook'], ENT_QUOTES, 'UTF-8'); ?></p>
      <?php endif; ?>

      <?php if (!empty($book['tags'])): ?>
        <div class="tag-list">
          <?php foreach ($book['tags'] as $tag): ?>
            <span class="tag"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($book['status'] === 'coming-soon'): ?>
        <p class="book-badge book-badge--coming-soon">Coming Soon</p>
      <?php endif; ?>

      <?php
      $buy_links = $book['buy_links'] ?? [];
      include $project_root . '/includes/components/buy-links.php';
      ?>
    </div>
  </section>

  <div class="divider-gear"></div>

  <!-- Core Premise -->
  <?php if (!empty($book['premise'])): ?>
    <section class="section">
      <p class="section-label">What This Book Is About</p>
      <h2>Core Premise</h2>
      <div class="book-prose">
        <?php echo '<p>' . nl2br(htmlspecialchars($book['premise'], ENT_QUOTES, 'UTF-8')) . '</p>'; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- What Readers Will Learn -->
  <?php if (!empty($book['what_readers_learn'])): ?>
    <div class="divider-gear"></div>
    <section class="section">
      <p class="section-label">Key Takeaways</p>
      <h2>What You Will Learn</h2>
      <ul class="benefit-list">
        <?php foreach ($book['what_readers_learn'] as $item): ?>
          <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <!-- Key Themes -->
  <?php if (!empty($book['key_themes'])): ?>
    <div class="divider-gear"></div>
    <section class="section">
      <p class="section-label">Themes</p>
      <h2>Key Themes</h2>
      <div class="tag-list tag-list--lg">
        <?php foreach ($book['key_themes'] as $theme): ?>
          <span class="tag tag--lg"><?php echo htmlspecialchars(ucfirst($theme), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Excerpt / Sample Chapter -->
  <?php if (!empty($book['excerpt'])): ?>
    <div class="divider-gear"></div>
    <section class="section">
      <p class="section-label">Read a Sample</p>
      <h2>Excerpt</h2>
      <blockquote class="book-excerpt">
        <?php echo nl2br(htmlspecialchars($book['excerpt'], ENT_QUOTES, 'UTF-8')); ?>
      </blockquote>
    </section>
  <?php endif; ?>

  <!-- Endorsements -->
  <?php if (!empty($book['endorsements'])): ?>
    <div class="divider-gear"></div>
    <section class="section">
      <p class="section-label">What Others Say</p>
      <h2>Endorsements</h2>
      <div class="review-grid">
        <?php foreach ($book['endorsements'] as $endorsement): ?>
          <div class="review-card">
            <blockquote>
              <p>&ldquo;<?php echo htmlspecialchars($endorsement['text'], ENT_QUOTES, 'UTF-8'); ?>&rdquo;</p>
            </blockquote>
            <cite>
              &mdash; <?php echo htmlspecialchars($endorsement['name'], ENT_QUOTES, 'UTF-8'); ?>
              <?php if (!empty($endorsement['title'])): ?>
                <span class="review-source"><?php echo htmlspecialchars($endorsement['title'], ENT_QUOTES, 'UTF-8'); ?></span>
              <?php endif; ?>
            </cite>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Related Books -->
  <?php
  $related_books = get_related_books($book, 'non-fiction');
  $related_type = 'non-fiction';
  include $project_root . '/includes/components/related-books.php';
  ?>

  <!-- ARC Reader Club CTA -->
  <div class="divider-gear"></div>
  <section class="section text-center">
    <h2>Get Early Access to Upcoming Releases</h2>
    <p class="lead" style="margin:0 auto var(--space-lg)">Join the ARC Reader Club for advance copies, insider updates, and the chance to support each launch.</p>
    <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
  </section>

</main>
<?php require_once $project_root . '/includes/footer.php'; ?>
