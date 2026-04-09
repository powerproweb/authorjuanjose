<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/book-catalog.php';

$page_title = 'Book Series | AuthorJuanJose.io';
require_once dirname(__DIR__) . '/includes/header.php';

global $series_catalog;
?>

<main class="container page-shell">

  <h1>Book Series</h1>
  <p class="lead">Follow each series from start to finish — every story builds on the world before it.</p>

  <hr class="ornament-rule">

  <?php if (count($series_catalog) > 0): ?>
    <div class="card-grid">
      <?php foreach ($series_catalog as $s): ?>
        <a class="book-card" href="/series/<?php echo htmlspecialchars($s['slug'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php if (!empty($s['cover'])): ?>
            <div class="book-card__cover">
              <img src="<?php echo htmlspecialchars($s['cover'], ENT_QUOTES, 'UTF-8'); ?>"
                   alt="<?php echo htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8'); ?>"
                   loading="lazy">
            </div>
          <?php endif; ?>
          <div class="book-card__info">
            <h3><?php echo htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p><?php echo htmlspecialchars($s['description'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="book-card__series">
              <?php echo count($s['books']); ?> book<?php echo count($s['books']) !== 1 ? 's' : ''; ?>
              &middot;
              <?php echo $s['status'] === 'in-progress' ? 'In progress' : 'Complete'; ?>
            </p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>Series information will be available as books are published.</p>
  <?php endif; ?>

  <div class="divider-gear"></div>

  <section class="section text-center">
    <h2>Follow the Journey</h2>
    <p class="lead" style="margin:0 auto var(--space-lg)">Be the first to know when new series installments are released.</p>
    <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
  </section>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
