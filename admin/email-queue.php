<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = get_db();
$message = '';

// Handle manual send trigger
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'process_queue') {
    ob_start();
    require dirname(__DIR__) . '/cron/send-emails.php';
    $message = 'Queue processed. ' . trim(ob_get_clean());
}

// Stats
$pending = (int)$pdo->query('SELECT COUNT(*) FROM email_queue WHERE status = "pending"')->fetchColumn();
$sent_count = (int)$pdo->query('SELECT COUNT(*) FROM email_queue WHERE status = "sent"')->fetchColumn();
$failed_count = (int)$pdo->query('SELECT COUNT(*) FROM email_queue WHERE status = "failed"')->fetchColumn();

// Filter
$filter = (string)($_GET['filter'] ?? 'all');
$where = '';
if ($filter === 'pending') $where = 'WHERE status = "pending"';
elseif ($filter === 'sent') $where = 'WHERE status = "sent"';
elseif ($filter === 'failed') $where = 'WHERE status = "failed"';

$emails = $pdo->query("SELECT * FROM email_queue {$where} ORDER BY scheduled_at DESC LIMIT 100")->fetchAll();

$page_title = 'Email Queue | Admin';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Email Queue</h1>
  <p><a href="/admin">&larr; Admin Dashboard</a></p>

  <?php if ($message !== ''): ?>
    <div class="alert alert--success" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="card-grid">
    <div class="card text-center">
      <h3 class="mt-0" style="font-size:2rem;color:var(--accent)"><?php echo $pending; ?></h3>
      <p class="mb-0">Pending</p>
    </div>
    <div class="card text-center">
      <h3 class="mt-0" style="font-size:2rem;color:var(--success)"><?php echo $sent_count; ?></h3>
      <p class="mb-0">Sent</p>
    </div>
    <div class="card text-center">
      <h3 class="mt-0" style="font-size:2rem;color:var(--danger)"><?php echo $failed_count; ?></h3>
      <p class="mb-0">Failed</p>
    </div>
  </div>

  <!-- Manual trigger -->
  <?php if ($pending > 0): ?>
    <form method="post" class="mt-lg">
      <button class="button button--lg" type="submit" name="action" value="process_queue">Process Queue Now (<?php echo $pending; ?> pending)</button>
    </form>
  <?php endif; ?>

  <div class="divider-gear"></div>

  <!-- Filters -->
  <div class="admin-filters">
    <a class="button <?php echo $filter === 'all' ? '' : 'button--outline'; ?>" href="/admin/email-queue">All</a>
    <a class="button <?php echo $filter === 'pending' ? '' : 'button--outline'; ?>" href="/admin/email-queue?filter=pending">Pending</a>
    <a class="button <?php echo $filter === 'sent' ? '' : 'button--outline'; ?>" href="/admin/email-queue?filter=sent">Sent</a>
    <a class="button <?php echo $filter === 'failed' ? '' : 'button--outline'; ?>" href="/admin/email-queue?filter=failed">Failed</a>
  </div>

  <!-- Queue List -->
  <?php if (count($emails) > 0): ?>
    <div class="admin-list">
      <?php foreach ($emails as $e): ?>
        <div class="admin-list-item">
          <div class="admin-list-item__info">
            <strong><?php echo htmlspecialchars($e['template_key'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <span class="admin-list-item__email"><?php echo htmlspecialchars($e['recipient_email'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="admin-list-item__meta">
              Lang: <?php echo htmlspecialchars($e['language'], ENT_QUOTES, 'UTF-8'); ?>
              &middot; Scheduled: <?php echo htmlspecialchars($e['scheduled_at'], ENT_QUOTES, 'UTF-8'); ?>
              <?php if (!empty($e['sent_at'])): ?>
                &middot; Sent: <?php echo htmlspecialchars($e['sent_at'], ENT_QUOTES, 'UTF-8'); ?>
              <?php endif; ?>
            </span>
            <span class="campaign-status campaign-status--<?php echo $e['status'] === 'sent' ? 'completed' : ($e['status'] === 'failed' ? 'declined' : 'invited'); ?>">
              <?php echo ucfirst(htmlspecialchars($e['status'], ENT_QUOTES, 'UTF-8')); ?>
            </span>
            <?php if ($e['error_message'] !== ''): ?>
              <span style="color:var(--danger);font-size:.85rem"><?php echo htmlspecialchars($e['error_message'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No emails match this filter.</p>
  <?php endif; ?>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
