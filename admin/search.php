<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/search-index.php';

$pdo = get_db();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rebuild') {
    $count = rebuild_search_index();
    $message = "Search index rebuilt successfully. {$count} items indexed.";
}

$total = (int)$pdo->query('SELECT COUNT(*) FROM search_index')->fetchColumn();
$by_type = $pdo->query('SELECT content_type, COUNT(*) as cnt FROM search_index GROUP BY content_type ORDER BY content_type')->fetchAll();

$page_title = 'Search Index | Admin';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Search Index</h1>
  <p><a href="/admin">&larr; Admin Dashboard</a></p>

  <?php if ($message !== ''): ?>
    <div class="alert alert--success" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <div class="card-grid">
    <div class="card text-center">
      <h3 class="mt-0" style="font-size:2rem;color:var(--accent)"><?php echo $total; ?></h3>
      <p class="mb-0">Total Indexed Items</p>
    </div>
    <?php foreach ($by_type as $t): ?>
      <div class="card text-center">
        <h3 class="mt-0" style="font-size:2rem;color:var(--accent)"><?php echo (int)$t['cnt']; ?></h3>
        <p class="mb-0"><?php echo ucfirst(htmlspecialchars($t['content_type'], ENT_QUOTES, 'UTF-8')); ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <form method="post" class="mt-lg">
    <button class="button button--lg" type="submit" name="action" value="rebuild">Rebuild Search Index</button>
  </form>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
