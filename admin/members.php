<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = get_db();
$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $action    = (string)($_POST['action'] ?? '');

    if ($member_id > 0) {
        if ($action === 'approve') {
            // Generate temporary password
            $temp_password = bin2hex(random_bytes(6)); // 12-char hex
            $hash = password_hash($temp_password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare('UPDATE members SET status = "active", password_hash = ?, approved_at = datetime("now") WHERE id = ? AND status = "pending"');
            $stmt->execute([$hash, $member_id]);

            if ($stmt->rowCount() > 0) {
                $message = 'Member #' . $member_id . ' approved. Temporary password: ' . $temp_password . ' — provide this to the member securely.';
            }
        } elseif ($action === 'suspend') {
            $pdo->prepare('UPDATE members SET status = "suspended" WHERE id = ?')->execute([$member_id]);
            $message = 'Member #' . $member_id . ' suspended.';
        } elseif ($action === 'activate') {
            $pdo->prepare('UPDATE members SET status = "active" WHERE id = ? AND status = "suspended"')->execute([$member_id]);
            $message = 'Member #' . $member_id . ' reactivated.';
        } elseif ($action === 'reset_password') {
            $temp_password = bin2hex(random_bytes(6));
            $hash = password_hash($temp_password, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE members SET password_hash = ? WHERE id = ?')->execute([$hash, $member_id]);
            $message = 'Password reset for member #' . $member_id . '. New password: ' . $temp_password;
        }
    }
}

// Filter
$filter = (string)($_GET['status'] ?? 'all');
$valid_filters = ['all', 'pending', 'active', 'suspended'];
if (!in_array($filter, $valid_filters, true)) {
    $filter = 'all';
}

if ($filter === 'all') {
    $members = $pdo->query('SELECT * FROM members ORDER BY joined_at DESC')->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT * FROM members WHERE status = ? ORDER BY joined_at DESC');
    $stmt->execute([$filter]);
    $members = $stmt->fetchAll();
}

$page_title = 'Manage Members | Admin';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Manage Members</h1>
  <p><a href="/admin">&larr; Admin Dashboard</a></p>

  <?php if ($message !== ''): ?>
    <div class="alert alert--success" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <!-- Filters -->
  <div class="admin-filters">
    <?php foreach ($valid_filters as $f): ?>
      <a class="button <?php echo $filter === $f ? '' : 'button--outline'; ?>"
         href="/admin/members<?php echo $f !== 'all' ? '?status=' . $f : ''; ?>">
        <?php echo ucfirst($f); ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Members List -->
  <?php if (count($members) > 0): ?>
    <div class="admin-list">
      <?php foreach ($members as $m): ?>
        <div class="admin-list-item">
          <div class="admin-list-item__info">
            <strong><?php echo htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <span class="admin-list-item__email"><?php echo htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="campaign-status campaign-status--<?php echo htmlspecialchars($m['status'], ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo ucfirst(htmlspecialchars($m['status'], ENT_QUOTES, 'UTF-8')); ?>
            </span>
            <span class="admin-list-item__meta">
              Tier <?php echo (int)$m['tier']; ?> &middot;
              <?php echo (int)$m['review_count']; ?> review<?php echo (int)$m['review_count'] !== 1 ? 's' : ''; ?> &middot;
              <?php echo htmlspecialchars($m['language'], ENT_QUOTES, 'UTF-8'); ?> &middot;
              Joined <?php echo htmlspecialchars($m['joined_at'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
          </div>
          <div class="admin-list-item__actions">
            <form method="post" style="display:inline">
              <input type="hidden" name="member_id" value="<?php echo (int)$m['id']; ?>">
              <?php if ($m['status'] === 'pending'): ?>
                <button class="button" type="submit" name="action" value="approve">Approve</button>
              <?php elseif ($m['status'] === 'active'): ?>
                <button class="button button--outline" type="submit" name="action" value="suspend">Suspend</button>
                <button class="button button--outline" type="submit" name="action" value="reset_password">Reset Password</button>
              <?php elseif ($m['status'] === 'suspended'): ?>
                <button class="button" type="submit" name="action" value="activate">Reactivate</button>
              <?php endif; ?>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No members match this filter.</p>
  <?php endif; ?>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
