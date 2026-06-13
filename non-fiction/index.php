<?php
declare(strict_types=1);


$page_title = 'Non-Fiction | AuthorJuanJose.io';
$page_description = 'Non-fiction by Author Juan Jose. Real-world insight, practical wisdom, and the thinking behind the stories.';
$body_class = 'cards-4up';
require_once dirname(__DIR__) . '/includes/header.php';
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
  <section class="section">
    <p class="section-label">Non-Fiction</p>
    <h2>Non-Fiction Catalog Placeholder</h2>
    <?php
    $placeholder_collection = 'Non-Fiction';
    $placeholder_count = 8;
    $placeholder_intro = 'Eight placeholder slots are active while non-fiction titles are being staged.';
    require dirname(__DIR__) . '/includes/components/book-placeholder-list.php';
    ?>
  </section>

  <!-- ARC CTA -->
  <div class="divider-gear"></div>
  <section class="section text-center">
    <h2>Get Early Access</h2>
    <p class="lead" style="margin:0 auto var(--space-lg)">ARC Reader Club members get advance copies of upcoming non-fiction releases.</p>
    <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
  </section>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
