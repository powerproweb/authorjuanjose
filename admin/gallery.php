<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = get_db();
$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_id = (int)($_POST['upload_id'] ?? 0);
    $action    = (string)($_POST['action'] ?? '');

    if ($upload_id > 0) {
        if ($action === 'approve') {
            $pdo->prepare('UPDATE gallery_uploads SET status = "approved", approved_at = datetime("now") WHERE id = ?')
                ->execute([$upload_id]);
            $message = 'Upload #' . $upload_id . ' approved.';
        } elseif ($action === 'reject') {
            $pdo->prepare('UPDATE gallery_uploads SET status = "rejected" WHERE id = ?')
                ->execute([$upload_id]);
            $message = 'Upload #' . $upload_id . ' rejected.';
        } elseif ($action === 'delete') {
            // Get paths to delete files
            $stmt = $pdo->prepare('SELECT image_path, thumbnail_path FROM gallery_uploads WHERE id = ?');
            $stmt->execute([$upload_id]);
            $upload = $stmt->fetch();
            if ($upload) {
                $base = dirname(__DIR__);
                @unlink($base . $upload['image_path']);
                @unlink($base . $upload['thumbnail_path']);
                $pdo->prepare('DELETE FROM gallery_uploads WHERE id = ?')->execute([$upload_id]);
                $message = 'Upload #' . $upload_id . ' deleted.';
            }
        }
    }
}

// Filter
$filter = (string)($_GET['filter'] ?? 'pending');
$where = match($filter) {
    'approved' => 'WHERE g.status = "approved"',
    'rejected' => 'WHERE g.status = "rejected"',
    'all'      => '',
    default    => 'WHERE g.status = "pending"',
};

$uploads = $pdo->query("
    SELECT g.*, m.name AS member_name, m.email AS member_email
    FROM gallery_uploads g
    JOIN members m ON m.id = g.member_id
    {$where}
    ORDER BY g.uploaded_at DESC
    LIMIT 100
")->fetchAll();

$page_title = 'Gallery Moderation | Admin';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Gallery Moderation</h1>
  <p><a href="/admin">&larr; Admin Dashboard</a></p>

  <?php if ($message !== ''): ?>
    <div class="alert alert--success" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <!-- Filters -->
  <div class="admin-filters">
    <a class="button <?php echo $filter === 'pending' ? '' : 'button--outline'; ?>" href="/admin/gallery?filter=pending">Pending</a>
    <a class="button <?php echo $filter === 'approved' ? '' : 'button--outline'; ?>" href="/admin/gallery?filter=approved">Approved</a>
    <a class="button <?php echo $filter === 'rejected' ? '' : 'button--outline'; ?>" href="/admin/gallery?filter=rejected">Rejected</a>
    <a class="button <?php echo $filter === 'all' ? '' : 'button--outline'; ?>" href="/admin/gallery?filter=all">All</a>
  </div>

  <?php if (count($uploads) > 0): ?>
    <div class="gallery-grid gallery-grid--admin">
      <?php foreach ($uploads as $u): ?>
        <div class="gallery-item gallery-item--admin">
          <img src="<?php echo htmlspecialchars($u['thumbnail_path'] !== '' ? $u['thumbnail_path'] : $u['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
               alt="Upload by <?php echo htmlspecialchars($u['member_name'], ENT_QUOTES, 'UTF-8'); ?>"
               loading="lazy">
          <div class="gallery-item__info">
            <strong><?php echo htmlspecialchars($u['member_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <span class="admin-list-item__meta"><?php echo htmlspecialchars($u['member_email'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php if ($u['caption'] !== ''): ?>
              <p style="font-size:.9rem"><?php echo htmlspecialchars($u['caption'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <span class="campaign-status campaign-status--<?php echo $u['status'] === 'approved' ? 'completed' : ($u['status'] === 'rejected' ? 'declined' : 'invited'); ?>">
              <?php echo ucfirst(htmlspecialchars($u['status'], ENT_QUOTES, 'UTF-8')); ?>
            </span>
            <form method="post" class="mt-md" style="display:flex;gap:var(--space-xs);flex-wrap:wrap">
              <input type="hidden" name="upload_id" value="<?php echo (int)$u['id']; ?>">
              <?php if ($u['status'] !== 'approved'): ?>
                <button class="button" type="submit" name="action" value="approve">Approve</button>
              <?php endif; ?>
              <?php if ($u['status'] !== 'rejected'): ?>
                <button class="button button--outline" type="submit" name="action" value="reject">Reject</button>
              <?php endif; ?>
              <button class="button button--outline" type="submit" name="action" value="delete" onclick="return confirm('Delete this upload permanently?')">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No uploads match this filter.</p>
  <?php endif; ?>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
