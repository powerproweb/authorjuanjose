<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/book-catalog.php';

$page_title = 'Non-Fiction | AuthorJuanJose.io';
require_once dirname(__DIR__) . '/includes/header.php';

$published   = get_nonfiction_books('published');
$coming_soon = get_nonfiction_books('coming-soon');
?>

<!-- Hero -->
<section class="hero">
  <div class="container">
    <p class="section-label" style="color:rgba(255,255,255,.7)">Non-Fiction</p>
    <h1>Ideas That Matter</h1>
    <p class="lead">Real-world insight, practical wisdom, and the thinking behind the stories.</p>
    <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
  </div>
</section>

<main class="container page-shell">

  <!-- Published Titles -->
  <?php if (count($published) > 0): ?>
    <section class="section">
      <p class="section-label">Available Now</p>
      <h2>Published Non-Fiction</h2>
      <div class="card-grid">
        <?php foreach ($published as $book): ?>
          <a class="book-card" href="/non-fiction/<?php echo htmlspecialchars($book['slug'], ENT_QUOTES, 'UTF-8'); ?>">
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

  <!-- Empty state if no books yet -->
  <?php if (count($published) === 0 && count($coming_soon) === 0): ?>
    <section class="section">
      <p class="section-label">Non-Fiction</p>
      <h2>Titles Coming Soon</h2>
      <p>Non-fiction works are in development. Join the ARC Reader Club to be the first to know when new titles are announced.</p>
    </section>
  <?php endif; ?>

  <!-- ARC CTA -->
  <div class="divider-gear"></div>
  <section class="section text-center">
    <h2>Get Early Access</h2>
    <p class="lead" style="margin:0 auto var(--space-lg)">ARC Reader Club members get advance copies of upcoming non-fiction releases.</p>
    <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
  </section>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
