<?php
declare(strict_types=1);


$page_title = 'Fiction | AuthorJuanJose.io';
$page_description = 'Steampunk science fiction by Author Juan Jose. Immersive worlds, futuristic steam-driven technology, and stories that stay with you.';
require_once dirname(__DIR__) . '/includes/header.php';
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
  <section class="section">
    <p class="section-label">Fiction</p>
    <h2>Fiction Catalog Placeholder</h2>
    <?php
    $placeholder_collection = 'Fiction';
    $placeholder_count = 8;
    $placeholder_intro = 'Eight placeholder slots are active while fiction titles are being staged.';
    require dirname(__DIR__) . '/includes/components/book-placeholder-list.php';
    ?>
  </section>

  <!-- CTA -->
  <div class="divider-gear"></div>
  <section class="section text-center">
    <h2>Never Miss a New Release</h2>
    <p class="lead" style="margin:0 auto var(--space-lg)">Be the first to read upcoming steampunk adventures.</p>
    <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
  </section>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
