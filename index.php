<?php
declare(strict_types=1);

$page_title = 'Author Juan Jose | Steampunk Science Fiction';
$page_description = 'Official website of Author Juan Jose. Steampunk science fiction, non-fiction, the ARC Reader Club, and immersive worlds built on steam-powered possibility.';
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero hero--aperture hero--home">
  <div class="container">
    <p class="section-label">Author Juan Jose</p>
    <h1>Steampunk Science Fiction for the Curious Mind</h1>
    <p class="lead">Immersive worlds. Futuristic steam-driven technology. Stories that stay with you long after the last page.</p>
    <p class="hero-intro-note">Step into cinematic worlds engineered with brass, fire, and relentless momentum.</p>
    <div class="hero-actions">
      <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
      <a class="button button--outline button--lg" href="/fiction">Browse Books</a>
    </div>
  </div>
</section>

<main id="main-content" class="container page-shell">
  <section class="section section--book-feature section--featured-release">
    <p class="section-label">Featured Release</p>
    <h2>Jules Verne&rsquo;s Michael Strogoff, or The Courier of the Czar</h2>
    <div class="book-feature-float" aria-label="Michael Strogoff covers">
      <div class="book-feature-float__cover">
        <img src="/assets/images/book_covers/michael_strogoff_me_bk1_cvr.jpg" alt="Michael Strogoff, Book One cover" loading="lazy">
      </div>
      <div class="book-feature-float__cover">
        <img src="/assets/images/book_covers/michael_strogoff_me_bk2_cvr.jpg" alt="Michael Strogoff, Book Two cover" loading="lazy">
      </div>
    </div>
    <p>A gripping two-book adventure novel set in 19th-century Russia during a Tartar rebellion against the Czar. The story follows Michael Strogoff, a brave and resourceful courier entrusted with a vital mission: to deliver an urgent message to the governor of Irkutsk and warn him of a treacherous plot led by the cunning traitor Ivan Ogareff.</p>
    <p>Traveling over 5,000 miles through the harsh Siberian landscape, Michael faces relentless dangers, including enemy spies, brutal Tartar warriors, and the ever-present threat of exposure. Combining elements of historical fiction, adventure, and espionage, Verne&rsquo;s novel is a testament to courage, duty, and resilience.</p>
    <p class="mt-lg">
      <a class="button" href="/fiction/michael-strogoff">Read More</a>
      <a class="button button--outline" href="/fiction">Browse Fiction</a>
    </p>
  </section>

  <section class="section section--book-feature">
    <p class="section-label">Steampunk Coloring Books</p>
    <h2>Steampunk Coloring Books</h2>
    <div class="book-feature-float" aria-label="Steampunk coloring book covers">
      <div class="book-feature-float__cover">
        <img src="/assets/images/book_covers/atticus_cvrs_6x9_800x1184_sp_colorbk_1-16.jpg" alt="Steampunk coloring book volume one cover" loading="lazy">
      </div>
      <div class="book-feature-float__cover">
        <img src="/assets/images/book_covers/atticus_cvrs_6x9_800x1184_sp_colorbk_2-16.jpg" alt="Steampunk coloring book volume two cover" loading="lazy">
      </div>
    </div>
    <p>A dedicated two-volume coloring collection is in development, featuring intricate steampunk machinery, airships, and worldbuilding motifs designed for immersive creative sessions.</p>
    <p>This block is set as a placeholder section so final covers, descriptions, and release links can be dropped in as soon as assets are ready.</p>
    <p class="section-status"><span class="tag">IN DEVELOPMENT</span></p>
  </section>

  <div class="divider-gear"></div>

  <section class="section">
    <p class="section-label">The ARC Reader Club</p>
    <h2>Read Early. Support the Launch. Step Inside the World Before Release Day.</h2>
    <p>The ARC Reader Club is the official early reader community for Author Juan Jose. Members get advance access to upcoming steampunk science fiction releases, exclusive insider updates, and the chance to support each launch before the rest of the world arrives.</p>
    <div class="card-grid mt-lg">
      <div class="card">
        <h3>Early Access</h3>
        <p>Receive advance reader copies before public release and be among the first to experience new worlds.</p>
      </div>
      <div class="card">
        <h3>Insider Updates</h3>
        <p>Behind-the-scenes content, cover reveals, selected excerpts, and launch announcements delivered to your inbox.</p>
      </div>
      <div class="card">
        <h3>Recognition</h3>
        <p>Active readers earn status through milestone tiers, featured recognition, and future commemorative rewards.</p>
      </div>
    </div>
    <p class="mt-lg"><a class="button" href="/arc-reader-club">Learn More About the Club</a></p>
  </section>

  <div class="divider-gear"></div>

  <section class="section">
    <p class="section-label">Story Worlds</p>
    <h2>Enter the Expanding Storyverse</h2>
    <p>The site is evolving into a full world hub with linked portals for every major universe. Canon labels and route names are currently being finalized with the author.</p>
    <div class="card-grid mt-lg">
      <article class="card">
        <h3>Classic Adventure Reimagined</h3>
        <p>Michael Strogoff and high-pressure imperial-era missions remastered for modern readers.</p>
      </article>
      <article class="card">
        <h3>Steampunk Dreamscape</h3>
        <p>Illustrated mechanical futures anchored in steam, invention, and atmospheric worldbuilding.</p>
      </article>
      <article class="card">
        <h3>Additional Universes</h3>
        <p>Reserved for author-confirmed canon expansions and multi-series continuity arcs.</p>
      </article>
    </div>
  </section>

  <div class="divider-gear"></div>

  <section class="section">
    <p class="section-label">About the Author</p>
    <h2>Juan Jose</h2>
    <p>A storyteller drawn to the intersection of steam-powered possibility and speculative futures. Crafting immersive worlds where resilience meets invention and where every journey begins with the turn of a gear.</p>
    <p><a class="button button--outline" href="/about">Read More</a></p>
  </section>

  <div class="divider-gear"></div>

  <section class="section section--newsletter">
    <p class="section-label">Signal Wire</p>
    <h2>Subscribe for Launch Dispatches</h2>
    <p>Choose how you want updates delivered and stay synced with fiction and non-fiction releases.</p>
    <?php
    $newsletter_heading = 'Stay Connected';
    $newsletter_subtext = 'Get updates on new releases, journal entries, and exclusive content. Choose what interests you.';
    $newsletter_style = 'inline';
    include __DIR__ . '/includes/components/newsletter-signup.php';
    ?>
  </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
