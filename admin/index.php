<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = get_db();

// Stats
$total_members   = (int)$pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
$pending_members = (int)$pdo->query('SELECT COUNT(*) FROM members WHERE status = "pending"')->fetchColumn();
$active_members  = (int)$pdo->query('SELECT COUNT(*) FROM members WHERE status = "active"')->fetchColumn();
$total_campaigns = (int)$pdo->query('SELECT COUNT(*) FROM campaigns')->fetchColumn();
$active_campaigns = (int)$pdo->query('SELECT COUNT(*) FROM campaigns WHERE status = "active"')->fetchColumn();
$total_reviews   = (int)$pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn();
$unverified      = (int)$pdo->query('SELECT COUNT(*) FROM reviews WHERE verified = 0')->fetchColumn();

$page_title = 'Admin | AuthorJuanJose.io';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Admin Dashboard</h1>
  <p class="lead">ARC Reader Club management.</p>

  <hr class="ornament-rule">

  <div class="card-grid">
    <div class="card text-center">
      <h3 class="mt-0" style="font-size:2rem;color:var(--accent)"><?php echo $total_members; ?></h3>
      <p class="mb-0">Total Members</p>
      <?php if ($pending_members > 0): ?>
        <p style="color:var(--danger);font-weight:600"><?php echo $pending_members; ?> pending approval</p>
      <?php endif; ?>
    </div>
    <div class="card text-center">
      <h3 class="mt-0" style="font-size:2rem;color:var(--accent)"><?php echo $total_campaigns; ?></h3>
      <p class="mb-0">Campaigns</p>
      <p style="color:var(--ink-light)"><?php echo $active_campaigns; ?> active</p>
    </div>
    <div class="card text-center">
      <h3 class="mt-0" style="font-size:2rem;color:var(--accent)"><?php echo $total_reviews; ?></h3>
      <p class="mb-0">Reviews</p>
      <?php if ($unverified > 0): ?>
        <p style="color:var(--danger);font-weight:600"><?php echo $unverified; ?> unverified</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="divider-gear"></div>

  <div class="card-grid">
    <a class="card" href="/admin/members" style="text-decoration:none;color:var(--ink)">
      <h3>Manage Members</h3>
      <p>Approve applications, view member list, manage statuses.</p>
    </a>
    <a class="card" href="/admin/campaigns" style="text-decoration:none;color:var(--ink)">
      <h3>Manage Campaigns</h3>
      <p>Create ARC campaigns, invite members, manage deadlines.</p>
    </a>
    <a class="card" href="/admin/reviews" style="text-decoration:none;color:var(--ink)">
      <h3>Review Moderation</h3>
      <p>View submitted reviews and verify them.</p>
    </a>
  </div>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
