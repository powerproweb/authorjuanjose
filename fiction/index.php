<?php
declare(strict_types=1);


$page_title = 'Fiction | AuthorJuanJose.io';
$page_description = 'Steampunk science fiction by Author Juan Jose. Immersive worlds, futuristic steam-driven technology, and stories that stay with you.';
$body_class = 'cards-4up';
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
    <h2>Fiction Cover Gallery</h2>
    <?php
    $placeholder_collection = 'Fiction';
    $placeholder_intro = 'Current fiction covers are now in place. Missing slots are filled with coming soon covers until links and descriptions are finalized.';
    $placeholder_items = [
        [
            'title' => 'Michael Strogoff, Modern Edition Book One',
            'tagline' => 'Fiction • Cover Ready',
            'cover' => '/assets/images/book_covers/michael_strogoff_me_bk1_cvr.jpg',
        ],
        [
            'title' => 'Michael Strogoff, Modern Edition Book Two',
            'tagline' => 'Fiction • Cover Ready',
            'cover' => '/assets/images/book_covers/michael_strogoff_me_bk2_cvr.jpg',
        ],
        [
            'title' => 'Michael Strogoff, Original Edition Book One',
            'tagline' => 'Fiction • Cover Ready',
            'cover' => '/assets/images/book_covers/michael_strogoff_oe_bk1_cvr.jpg',
        ],
        [
            'title' => 'Michael Strogoff, Original Edition Book Two',
            'tagline' => 'Fiction • Cover Ready',
            'cover' => '/assets/images/book_covers/michael_strogoff_oe_bk2_cvr.jpg',
        ],
        [
            'title' => 'Michael Strogoff, Spanish Edition Book One',
            'tagline' => 'Fiction • Cover Ready',
            'cover' => '/assets/images/book_covers/michael_strogoff_spa_bk1_cvr.jpg',
        ],
        [
            'title' => 'Michael Strogoff, Spanish Edition Book Two',
            'tagline' => 'Fiction • Cover Ready',
            'cover' => '/assets/images/book_covers/michael_strogoff_spa_bk2_cvr.jpg',
        ],
        [
            'title' => 'Michael Strogoff Box Set',
            'tagline' => 'Fiction • Cover Ready',
            'cover' => '/assets/images/book_covers/michael_strogoff_boxset_single_cvr.jpg',
        ],
        [
            'title' => 'The Agreement',
            'tagline' => 'Fiction • Cover Ready',
            'cover' => '/assets/images/book_covers/atticus_cvr_the_agreement_6x9_800x1184_100dpi_01.jpg',
        ],
        [
            'title' => 'Matrix Store',
            'tagline' => 'Fiction • Cover Ready',
            'cover' => '/assets/images/book_covers/atticus_cvrs_6x9_800x1184_matrix_store_01.jpg',
        ],
        [
            'title' => 'Steampunk Coloring Book Volume One',
            'tagline' => 'Fiction • Cover Ready',
            'cover' => '/assets/images/book_covers/atticus_cvrs_6x9_800x1184_sp_colorbk_1-16.jpg',
        ],
        [
            'title' => 'Steampunk Coloring Book Volume Two',
            'tagline' => 'Fiction • Cover Ready',
            'cover' => '/assets/images/book_covers/atticus_cvrs_6x9_800x1184_sp_colorbk_2-16.jpg',
        ],
        [
            'title' => 'Steampunk Coloring Sample One',
            'tagline' => 'Fiction • Cover Ready',
            'cover' => '/assets/images/book_covers/atticus_cvrs_6x9_800x1184_sp_colorbk_samp_1-2.jpg',
        ],
        [
            'title' => 'Steampunk Coloring Sample Two',
            'tagline' => 'Fiction • Cover Ready',
            'cover' => '/assets/images/book_covers/atticus_cvrs_6x9_800x1184_sp_colorbk_samp_2-2.jpg',
        ],
    ];
    $placeholder_row_size = 4;
    $placeholder_pad_to_row = true;
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
