<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/member-auth.php';

$member_name   = htmlspecialchars($arc_member['name'], ENT_QUOTES, 'UTF-8');
$member_tier   = (int)$arc_member['tier'];
$review_count  = (int)$arc_member['review_count'];
$tier_name     = get_tier_name($member_tier);
$tier_short    = get_tier_short($member_tier);
$tier_css      = get_tier_css_class($member_tier);
$tier_progress = get_tier_progress($member_tier, $review_count);

// Active campaigns for this member
$stmt = $pdo->prepare('
    SELECT c.*, ci.status AS invite_status
    FROM campaign_invites ci
    JOIN campaigns c ON c.id = ci.campaign_id
    WHERE ci.member_id = ? AND c.status = "active"
    ORDER BY c.review_deadline ASC
    LIMIT 5
');
$stmt->execute([$arc_member['id']]);
$active_campaigns = $stmt->fetchAll();

// Recent reviews
$stmt = $pdo->prepare('
    SELECT r.*, c.title AS campaign_title
    FROM reviews r
    JOIN campaigns c ON c.id = r.campaign_id
    WHERE r.member_id = ?
    ORDER BY r.submitted_at DESC
    LIMIT 5
');
$stmt->execute([$arc_member['id']]);
$recent_reviews = $stmt->fetchAll();

// Mark notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_read') {
    $pdo->prepare('UPDATE notifications SET read = 1 WHERE member_id = ? AND read = 0')->execute([$arc_member['id']]);
    header('Location: /arc-reader-club/dashboard');
    exit;
}

// Unread notifications
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE member_id = ? AND read = 0 ORDER BY created_at DESC LIMIT 10');
$stmt->execute([$arc_member['id']]);
$notifications = $stmt->fetchAll();

$page_title = 'Dashboard | ARC Reader Club';
$show_arc_sub_navigation = true;
$show_member_navigation = true;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Welcome back, <?php echo $member_name; ?></h1>
  <p class="lead">Your ARC Reader Club dashboard.</p>

  <hr class="ornament-rule">

  <!-- Notifications -->
  <?php if (count($notifications) > 0): ?>
    <section class="section">
      <p class="section-label">Notifications</p>
      <h2><?php echo count($notifications); ?> New</h2>
      <div class="notification-list">
        <?php foreach ($notifications as $n): ?>
          <div class="notification-item notification-item--<?php echo htmlspecialchars($n['type'], ENT_QUOTES, 'UTF-8'); ?>">
            <span class="notification-item__icon">
              <?php echo match($n['type']) { 'invite' => '&#128233;', 'reminder' => '&#9200;', 'promotion' => '&#127942;', default => '&#9881;' }; ?>
            </span>
            <span><?php echo htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="notification-item__date"><?php echo htmlspecialchars($n['created_at'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <form method="post" class="mt-md">
        <button class="button button--outline" type="submit" name="action" value="mark_read">Mark All as Read</button>
      </form>
    </section>
    <div class="divider-gear"></div>
  <?php endif; ?>

  <!-- Tier & Progress -->
  <section class="section">
    <div class="dash-tier">
      <div class="tier-icon tier-icon--<?php echo $tier_css; ?> dash-tier__icon">&#9881;</div>
      <div class="dash-tier__info">
        <p class="section-label"><?php echo $member_tier > 0 ? 'Tier ' . $member_tier : 'No Tier Yet'; ?></p>
        <h2 class="mt-0"><?php echo htmlspecialchars($tier_name, ENT_QUOTES, 'UTF-8'); ?></h2>
        <p><?php echo $review_count; ?> review<?php echo $review_count !== 1 ? 's' : ''; ?> submitted</p>

        <?php if ($tier_progress !== null): ?>
          <div class="progress-bar">
            <div class="progress-bar__fill progress-bar__fill--<?php echo $tier_css; ?>" style="width:<?php echo $tier_progress['progress_pct']; ?>%"></div>
          </div>
          <p class="progress-label">
            <?php echo $tier_progress['reviews_needed']; ?> more review<?php echo $tier_progress['reviews_needed'] !== 1 ? 's' : ''; ?>
            to reach <?php echo htmlspecialchars(get_tier_short($tier_progress['next_tier']), ENT_QUOTES, 'UTF-8'); ?>
          </p>
        <?php else: ?>
          <p class="progress-label">You have reached the highest distinction!</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <div class="divider-gear"></div>

  <!-- Active Campaigns -->
  <section class="section">
    <p class="section-label">Active Missions</p>
    <h2>Your Current Campaigns</h2>
    <?php if (count($active_campaigns) > 0): ?>
      <div class="campaign-list">
        <?php foreach ($active_campaigns as $c): ?>
          <div class="campaign-card">
            <div class="campaign-card__info">
              <h3><?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <?php if (!empty($c['review_deadline'])): ?>
                <p class="campaign-card__deadline">Deadline: <?php echo htmlspecialchars($c['review_deadline'], ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <span class="campaign-status campaign-status--<?php echo htmlspecialchars($c['invite_status'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo ucfirst(htmlspecialchars($c['invite_status'], ENT_QUOTES, 'UTF-8')); ?>
              </span>
            </div>
            <div class="campaign-card__actions">
              <?php if ($c['invite_status'] === 'accepted'): ?>
                <a class="button" href="/arc-reader-club/submit-review?campaign=<?php echo (int)$c['id']; ?>">Submit Review</a>
              <?php elseif ($c['invite_status'] === 'invited'): ?>
                <a class="button button--outline" href="/arc-reader-club/current-missions">View Details</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No active campaigns right now. New missions will appear here when available.</p>
    <?php endif; ?>
    <p class="mt-lg"><a href="/arc-reader-club/current-missions">View all missions &rarr;</a></p>
  </section>

  <div class="divider-gear"></div>

  <!-- Recent Reviews -->
  <section class="section">
    <p class="section-label">Recent Activity</p>
    <h2>Your Latest Reviews</h2>
    <?php if (count($recent_reviews) > 0): ?>
      <div class="review-list">
        <?php foreach ($recent_reviews as $r): ?>
          <div class="review-list-item">
            <div>
              <strong><?php echo htmlspecialchars($r['campaign_title'], ENT_QUOTES, 'UTF-8'); ?></strong>
              <span class="review-list-item__platform"><?php echo ucfirst(htmlspecialchars($r['platform'], ENT_QUOTES, 'UTF-8')); ?></span>
            </div>
            <span class="review-list-item__date"><?php echo htmlspecialchars($r['submitted_at'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No reviews submitted yet. Once you participate in a campaign, your reviews will appear here.</p>
    <?php endif; ?>
  </section>

  <!-- Quick Links -->
  <div class="divider-gear"></div>
  <section class="section">
    <div class="card-grid">
      <a class="card" href="/arc-reader-club/current-missions" style="text-decoration:none;color:var(--ink)">
        <h3>Current Missions</h3>
        <p>View your active and upcoming ARC campaigns.</p>
      </a>
      <a class="card" href="/arc-reader-club/my-distinctions" style="text-decoration:none;color:var(--ink)">
        <h3>My Distinctions</h3>
        <p>Track your tier progress and earned honors.</p>
      </a>
      <a class="card" href="/arc-reader-club/archive-record" style="text-decoration:none;color:var(--ink)">
        <h3>Archive Record</h3>
        <p>View your complete participation history.</p>
      </a>
    </div>
  </section>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
