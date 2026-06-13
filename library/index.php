<?php
declare(strict_types=1);

$page_title = 'Library | AuthorJuanJose.io';
$page_description = 'Browse all fiction and non-fiction catalog placeholders by Author Juan Jose.';
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="hero">
  <div class="container">
    <p class="section-label" style="color:rgba(255,255,255,.7)">Library</p>
    <h1>Book Library</h1>
    <p class="lead">A unified view of current catalog placeholders across fiction and non-fiction.</p>
    <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
  </div>
</section>

<main class="container page-shell">
  <section class="section">
    <p class="section-label">Library</p>
    <h2>Library Placeholder Stack</h2>
    <?php
    $placeholder_collection = 'Library';
    $placeholder_count = 8;
    $placeholder_intro = 'Eight placeholder slots are active while the full library catalog is being assembled.';
    require dirname(__DIR__) . '/includes/components/book-placeholder-list.php';
    ?>
  </section>

  <div class="divider-gear"></div>
  <section class="section text-center">
    <h2>Explore by Collection</h2>
    <p class="lead" style="margin:0 auto var(--space-lg)">Browse dedicated pages while the unified library rollout is in progress.</p>
    <p>
      <a class="button button--outline" href="/fiction/">Fiction</a>
      <a class="button button--outline" href="/non-fiction/">Non-Fiction</a>
    </p>
  </section>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
