<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/book-catalog.php';

$all_tags = get_all_tags();

$page_title = 'Browse by Tag | AuthorJuanJose.io';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Browse by Tag</h1>
  <p class="lead">Explore books by theme, genre, and subject across fiction and non-fiction.</p>

  <hr class="ornament-rule">

  <?php if (count($all_tags) > 0): ?>
    <div class="tag-cloud">
      <?php foreach ($all_tags as $tag => $count): ?>
        <a class="tag-cloud__item" href="/tags/view?tag=<?php echo urlencode($tag); ?>">
          <?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>
          <span class="tag-cloud__count"><?php echo $count; ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No tags available yet. Tags will appear as books are added to the catalog.</p>
  <?php endif; ?>

  <div class="divider-gear"></div>

  <section class="section text-center">
    <h2>Not Sure Where to Start?</h2>
    <p class="lead" style="margin:0 auto var(--space-lg)">Let us help you find the perfect book.</p>
    <a class="button button--lg" href="/start-here">Start Here</a>
    <a class="button button--outline button--lg" href="/library" style="margin-left:.5rem">Browse Library</a>
  </section>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
