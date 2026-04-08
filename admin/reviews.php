<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = get_db();
$message = '';

// Handle verify/unverify
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = (int)($_POST['review_id'] ?? 0);
    $action    = (string)($_POST['action'] ?? '');

    if ($review_id > 0 && in_array($action, ['verify', 'unverify'], true)) {
        $verified = $action === 'verify' ? 1 : 0;
        $pdo->prepare('UPDATE reviews SET verified = ? WHERE id = ?')->execute([$verified, $review_id]);
        $message = 'Review #' . $review_id . ($verified ? ' verified.' : ' unverified.');
    }
}

// Filter
$filter = (string)($_GET['filter'] ?? 'all');
$where = '';
if ($filter === 'unverified') {
    $where = 'WHERE r.verified = 0';
} elseif ($filter === 'verified') {
    $where = 'WHERE r.verified = 1';
}

$reviews = $pdo->query("
    SELECT r.*, m.name AS member_name, m.email AS member_email, c.title AS campaign_title
    FROM reviews r
    JOIN members m ON m.id = r.member_id
    JOIN campaigns c ON c.id = r.campaign_id
    $where
    ORDER BY r.submitted_at DESC
")->fetchAll();

$page_title = 'Review Moderation | Admin';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Review Moderation</h1>
  <p><a href="/admin">&larr; Admin Dashboard</a></p>

  <?php if ($message !== ''): ?>
    <div class="alert alert--success" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <!-- Filters -->
  <div class="admin-filters">
    <a class="button <?php echo $filter === 'all' ? '' : 'button--outline'; ?>" href="/admin/reviews">All</a>
    <a class="button <?php echo $filter === 'unverified' ? '' : 'button--outline'; ?>" href="/admin/reviews?filter=unverified">Unverified</a>
    <a class="button <?php echo $filter === 'verified' ? '' : 'button--outline'; ?>" href="/admin/reviews?filter=verified">Verified</a>
  </div>

  <?php if (count($reviews) > 0): ?>
    <div class="admin-list">
      <?php foreach ($reviews as $r): ?>
        <div class="admin-list-item">
          <div class="admin-list-item__info">
            <strong><?php echo htmlspecialchars($r['campaign_title'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <span class="admin-list-item__meta">
              By: <?php echo htmlspecialchars($r['member_name'], ENT_QUOTES, 'UTF-8'); ?>
              (<?php echo htmlspecialchars($r['member_email'], ENT_QUOTES, 'UTF-8'); ?>)
            </span>
            <span class="admin-list-item__meta">
              Platform: <?php echo ucfirst(htmlspecialchars($r['platform'], ENT_QUOTES, 'UTF-8')); ?>
              &middot; <?php echo htmlspecialchars($r['submitted_at'], ENT_QUOTES, 'UTF-8'); ?>
              <?php if ($r['verified']): ?>
                &middot; <span style="color:var(--success);font-weight:600">&#10003; Verified</span>
              <?php endif; ?>
            </span>
            <?php if (!empty($r['review_url'])): ?>
              <a href="<?php echo htmlspecialchars($r['review_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">View Review &rarr;</a>
            <?php endif; ?>
            <?php if (!empty($r['review_text'])): ?>
              <p style="font-size:.9rem;color:var(--ink-light);margin-top:var(--space-xs)">&ldquo;<?php echo htmlspecialchars(mb_substr($r['review_text'], 0, 200), ENT_QUOTES, 'UTF-8'); ?><?php echo mb_strlen($r['review_text']) > 200 ? '...' : ''; ?>&rdquo;</p>
            <?php endif; ?>
          </div>
          <div class="admin-list-item__actions">
            <form method="post" style="display:inline">
              <input type="hidden" name="review_id" value="<?php echo (int)$r['id']; ?>">
              <?php if (!$r['verified']): ?>
                <button class="button" type="submit" name="action" value="verify">Verify</button>
              <?php else: ?>
                <button class="button button--outline" type="submit" name="action" value="unverify">Unverify</button>
              <?php endif; ?>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No reviews match this filter.</p>
  <?php endif; ?>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
