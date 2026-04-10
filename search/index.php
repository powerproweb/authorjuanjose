<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/search-index.php';

$query   = trim((string)($_GET['q'] ?? ''));
$results = [];

if ($query !== '') {
    $results = search_index($query);
}

$page_title = ($query !== '' ? htmlspecialchars($query, ENT_QUOTES, 'UTF-8') . ' — ' : '') . 'Search | AuthorJuanJose.io';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Search</h1>

  <form method="get" action="/search" class="search-form">
    <div class="search-form__row">
      <input name="q" type="search" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>"
             placeholder="Search books, journal entries, and pages..." autofocus>
      <button class="button" type="submit">Search</button>
    </div>
  </form>

  <?php if ($query !== ''): ?>
    <hr class="ornament-rule">

    <?php if (count($results) > 0): ?>
      <p class="lead"><?php echo count($results); ?> result<?php echo count($results) !== 1 ? 's' : ''; ?> for &ldquo;<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>&rdquo;</p>

      <div class="search-results">
        <?php foreach ($results as $r): ?>
          <div class="search-result">
            <span class="book-badge book-badge--<?php echo htmlspecialchars($r['content_type'], ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo ucfirst(htmlspecialchars($r['content_type'], ENT_QUOTES, 'UTF-8')); ?>
            </span>
            <h3 class="search-result__title">
              <a href="<?php echo htmlspecialchars($r['url'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
            </h3>
            <?php if ($r['body_text'] !== ''): ?>
              <p class="search-result__excerpt"><?php echo highlight_excerpt($r['body_text'], $query); ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No results found for &ldquo;<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>&rdquo;. Try different keywords or <a href="/library">browse the library</a>.</p>
    <?php endif; ?>
  <?php endif; ?>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
