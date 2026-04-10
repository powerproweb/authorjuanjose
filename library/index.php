<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/book-catalog.php';

// Read filters from URL
$filter_type   = (string)($_GET['type'] ?? 'all');
$filter_tag    = strtolower(trim((string)($_GET['tag'] ?? '')));
$filter_status = (string)($_GET['status'] ?? 'all');

if (!in_array($filter_type, ['all', 'fiction', 'non-fiction'], true)) $filter_type = 'all';
if (!in_array($filter_status, ['all', 'published', 'coming-soon'], true)) $filter_status = 'all';

// Get books based on type filter
$books = [];
if ($filter_type === 'fiction' || $filter_type === 'all') {
    foreach (get_fiction_books() as $b) {
        $b['_type'] = 'fiction';
        $books[] = $b;
    }
}
if ($filter_type === 'non-fiction' || $filter_type === 'all') {
    foreach (get_nonfiction_books() as $b) {
        $b['_type'] = 'non-fiction';
        $books[] = $b;
    }
}

// Apply tag filter
if ($filter_tag !== '') {
    $books = array_filter($books, function (array $b) use ($filter_tag): bool {
        $tags = array_map('strtolower', $b['tags'] ?? []);
        return in_array($filter_tag, $tags, true);
    });
}

// Apply status filter
if ($filter_status !== 'all') {
    $books = array_filter($books, fn(array $b) => $b['status'] === $filter_status);
}

$books = array_values($books);
$all_tags = get_all_tags();

// Build query string helper
$qs = static function (array $overrides) use ($filter_type, $filter_tag, $filter_status): string {
    $params = array_filter([
        'type'   => $overrides['type'] ?? $filter_type,
        'tag'    => $overrides['tag'] ?? $filter_tag,
        'status' => $overrides['status'] ?? $filter_status,
    ], fn($v) => $v !== 'all' && $v !== '');
    return count($params) > 0 ? '?' . http_build_query($params) : '';
};

$page_title = 'Library | AuthorJuanJose.io';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Library</h1>
  <p class="lead">Browse all fiction and non-fiction titles. Filter by type, tag, or status.</p>

  <hr class="ornament-rule">

  <!-- Filters -->
  <div class="library-filters">
    <div class="library-filters__group">
      <strong>Type:</strong>
      <a class="button <?php echo $filter_type === 'all' ? '' : 'button--outline'; ?>" href="/library<?php echo $qs(['type' => 'all']); ?>">All</a>
      <a class="button <?php echo $filter_type === 'fiction' ? '' : 'button--outline'; ?>" href="/library<?php echo $qs(['type' => 'fiction']); ?>">Fiction</a>
      <a class="button <?php echo $filter_type === 'non-fiction' ? '' : 'button--outline'; ?>" href="/library<?php echo $qs(['type' => 'non-fiction']); ?>">Non-Fiction</a>
    </div>

    <div class="library-filters__group">
      <strong>Status:</strong>
      <a class="button <?php echo $filter_status === 'all' ? '' : 'button--outline'; ?>" href="/library<?php echo $qs(['status' => 'all']); ?>">All</a>
      <a class="button <?php echo $filter_status === 'published' ? '' : 'button--outline'; ?>" href="/library<?php echo $qs(['status' => 'published']); ?>">Published</a>
      <a class="button <?php echo $filter_status === 'coming-soon' ? '' : 'button--outline'; ?>" href="/library<?php echo $qs(['status' => 'coming-soon']); ?>">Coming Soon</a>
    </div>

    <?php if (count($all_tags) > 0): ?>
      <div class="library-filters__group">
        <strong>Tag:</strong>
        <?php if ($filter_tag !== ''): ?>
          <a class="button" href="/library<?php echo $qs(['tag' => '']); ?>">
            <?php echo htmlspecialchars($filter_tag, ENT_QUOTES, 'UTF-8'); ?> &times;
          </a>
        <?php else: ?>
          <?php foreach (array_slice(array_keys($all_tags), 0, 8) as $t): ?>
            <a class="button button--outline" href="/library<?php echo $qs(['tag' => $t]); ?>">
              <?php echo htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>
            </a>
          <?php endforeach; ?>
          <?php if (count($all_tags) > 8): ?>
            <a class="button button--outline" href="/tags">All tags &rarr;</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Results -->
  <?php if (count($books) > 0): ?>
    <p class="mt-lg" style="color:var(--ink-light)"><?php echo count($books); ?> title<?php echo count($books) !== 1 ? 's' : ''; ?></p>
    <div class="card-grid">
      <?php foreach ($books as $book): ?>
        <?php $url_prefix = $book['_type'] === 'fiction' ? '/fiction' : '/non-fiction'; ?>
        <a class="book-card" href="<?php echo $url_prefix . '/' . htmlspecialchars($book['slug'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php if (!empty($book['cover'])): ?>
            <div class="book-card__cover">
              <img src="<?php echo htmlspecialchars($book['cover'], ENT_QUOTES, 'UTF-8'); ?>"
                   alt="<?php echo htmlspecialchars($book['short_title'] ?? $book['title'], ENT_QUOTES, 'UTF-8'); ?>"
                   loading="lazy">
            </div>
          <?php endif; ?>
          <div class="book-card__info">
            <span class="book-badge book-badge--<?php echo $book['_type'] === 'fiction' ? 'fiction' : 'nonfiction'; ?>">
              <?php echo ucfirst($book['_type']); ?>
            </span>
            <h3><?php echo htmlspecialchars($book['short_title'] ?? $book['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p><?php echo htmlspecialchars($book['hook'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if (!empty($book['tags'])): ?>
              <div class="tag-list" style="margin-top:var(--space-sm)">
                <?php foreach (array_slice($book['tags'], 0, 3) as $t): ?>
                  <span class="tag"><?php echo htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="panel mt-lg">
      <p>No books match your current filters. Try adjusting your selection or <a href="/library">view all titles</a>.</p>
    </div>
  <?php endif; ?>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
