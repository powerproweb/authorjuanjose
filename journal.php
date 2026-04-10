<?php
declare(strict_types=1);

$page_title = 'Journal | AuthorJuanJose.io';
$page_description = 'Writing updates, behind-the-book notes, essays, and reflections from Author Juan Jose.';
require_once __DIR__ . '/includes/header.php';

/**
 * Journal entries are stored as PHP arrays below.
 * To add a new entry, add an item to $journal_entries.
 * Future enhancement: move to database or flat-file CMS.
 */
$journal_entries = [
    [
        'title'    => 'Welcome to the Journal',
        'date'     => '2026-04-09',
        'category' => 'Behind the Scenes',
        'excerpt'  => 'This is where the journey gets documented. Behind-the-book notes, writing updates, essays, reflections, and the thinking behind the work — all collected here.',
        'body'     => '<p>Welcome to the Author Juan Jos&eacute; Journal — a space for everything that happens between the covers.</p><p>Expect writing updates, behind-the-scenes notes on upcoming releases, reflections on the creative process, essays on topics that matter, and occasional deep dives into the steampunk worlds being built.</p><p>Whether you are here for the fiction, the non-fiction, or the ideas in between — this is where it all comes together.</p>',
        'slug'     => 'welcome-to-the-journal',
    ],
];

$categories = ['All', 'Fiction', 'Non-Fiction', 'Behind the Scenes'];
?>

<main class="container page-shell">

  <h1>Journal</h1>
  <p class="lead">Writing updates, behind-the-book notes, essays, reflections, and the thinking behind the work.</p>

  <hr class="ornament-rule">

  <!-- Category Filters -->
  <div class="admin-filters">
    <?php foreach ($categories as $cat): ?>
      <span class="button button--outline" style="cursor:default"><?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?></span>
    <?php endforeach; ?>
  </div>

  <!-- Entries -->
  <?php if (count($journal_entries) > 0): ?>
    <div class="journal-list">
      <?php foreach ($journal_entries as $entry): ?>
        <article class="journal-entry">
          <div class="journal-entry__meta">
            <span class="journal-entry__date"><?php echo htmlspecialchars($entry['date'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="tag"><?php echo htmlspecialchars($entry['category'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <h2 class="journal-entry__title"><?php echo htmlspecialchars($entry['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
          <div class="book-prose">
            <?php echo $entry['body']; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="panel">
      <p>Journal entries are coming soon. Check back for writing updates, essays, and behind-the-scenes content.</p>
    </div>
  <?php endif; ?>

  <div class="divider-gear"></div>

  <!-- CTA -->
  <section class="section text-center">
    <h2>Never Miss an Update</h2>
    <p class="lead" style="margin:0 auto var(--space-lg)">Join the ARC Reader Club for early access to new writing and exclusive behind-the-scenes content.</p>
    <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
  </section>

</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
