<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/book-catalog.php';

$page_title = 'Fiction | AuthorJuanJose.io';
require_once dirname(__DIR__) . '/includes/header.php';

$all_fiction = get_fiction_books();
$published   = get_fiction_books('published');
$coming_soon = get_fiction_books('coming-soon');
?>

<!-- Hero -->
<section class="hero">
  <div class="container">
    <p class="section-label" style="color:rgba(255,255,255,.7)">Fiction</p>
    <h1>Steampunk Science Fiction</h1>
    <p class="lead">Immersive worlds. Futuristic steam-driven technology. Stories that stay with you long after the last page.</p>
    <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
  </div>
</section>

<main class="container page-shell">

  <!-- Published Titles -->
  <?php if (count($published) > 0): ?>
    <section class="section">
      <p class="section-label">Available Now</p>
      <h2>Published Fiction</h2>
      <div class="card-grid">
        <?php foreach ($published as $book): ?>
          <a class="book-card" href="/fiction/<?php echo htmlspecialchars($book['slug'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php if (!empty($book['cover'])): ?>
              <div class="book-card__cover">
                <img src="<?php echo htmlspecialchars($book['cover'], ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo htmlspecialchars($book['short_title'] ?? $book['title'], ENT_QUOTES, 'UTF-8'); ?> cover"
                     loading="lazy">
              </div>
            <?php endif; ?>
            <div class="book-card__info">
              <h3><?php echo htmlspecialchars($book['short_title'] ?? $book['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p><?php echo htmlspecialchars($book['hook'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
              <?php if (!empty($book['series_slug'])): ?>
                <p class="book-card__series"><?php echo htmlspecialchars(get_book_series_name($book), ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Coming Soon -->
  <?php if (count($coming_soon) > 0): ?>
    <div class="divider-gear"></div>
    <section class="section">
      <p class="section-label">On the Horizon</p>
      <h2>Coming Soon</h2>
      <div class="card-grid">
        <?php foreach ($coming_soon as $book): ?>
          <div class="book-card book-card--coming-soon">
            <?php if (!empty($book['cover'])): ?>
              <div class="book-card__cover">
                <img src="<?php echo htmlspecialchars($book['cover'], ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo htmlspecialchars($book['short_title'] ?? $book['title'], ENT_QUOTES, 'UTF-8'); ?> cover"
                     loading="lazy">
              </div>
            <?php endif; ?>
            <div class="book-card__info">
              <h3><?php echo htmlspecialchars($book['short_title'] ?? $book['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p><?php echo htmlspecialchars($book['hook'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
              <span class="book-badge book-badge--coming-soon">Coming Soon</span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Series -->
  <?php
  global $series_catalog;
  $fiction_series = array_filter($series_catalog, fn($s) => count($s['books'] ?? []) > 0);
  ?>
  <?php if (count($fiction_series) > 0): ?>
    <div class="divider-gear"></div>
    <section class="section">
      <p class="section-label">Series</p>
      <h2>Book Series</h2>
      <div class="card-grid">
        <?php foreach ($fiction_series as $s): ?>
          <a class="book-card" href="/series/<?php echo htmlspecialchars($s['slug'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="book-card__info">
              <h3><?php echo htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p><?php echo htmlspecialchars($s['description'], ENT_QUOTES, 'UTF-8'); ?></p>
              <p class="book-card__series"><?php echo count($s['books']); ?> book<?php echo count($s['books']) !== 1 ? 's' : ''; ?> &middot; <?php echo $s['status'] === 'in-progress' ? 'In progress' : 'Complete'; ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- CTA -->
  <div class="divider-gear"></div>
  <section class="section text-center">
    <h2>Never Miss a New Release</h2>
    <p class="lead" style="margin:0 auto var(--space-lg)">Be the first to read upcoming steampunk adventures.</p>
    <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
  </section>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
