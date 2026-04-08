<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/member-auth.php';

// All campaign participation
$stmt = $pdo->prepare('
    SELECT c.*, ci.status AS invite_status, ci.invited_at, ci.accepted_at
    FROM campaign_invites ci
    JOIN campaigns c ON c.id = ci.campaign_id
    WHERE ci.member_id = ?
    ORDER BY ci.invited_at DESC
');
$stmt->execute([$arc_member['id']]);
$all_campaigns = $stmt->fetchAll();

// All reviews
$stmt = $pdo->prepare('
    SELECT r.*, c.title AS campaign_title
    FROM reviews r
    JOIN campaigns c ON c.id = r.campaign_id
    WHERE r.member_id = ?
    ORDER BY r.submitted_at DESC
');
$stmt->execute([$arc_member['id']]);
$all_reviews = $stmt->fetchAll();

$page_title = 'Archive Record | ARC Reader Club';
$show_arc_sub_navigation = true;
$show_member_navigation = true;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Archive Record</h1>
  <p class="lead">Your complete ARC Reader Club participation history.</p>

  <hr class="ornament-rule">

  <!-- Summary -->
  <section class="section">
    <div class="card-grid">
      <div class="card text-center">
        <h3 class="mt-0" style="font-size:2rem;color:var(--accent)"><?php echo count($all_campaigns); ?></h3>
        <p class="mb-0">Campaign<?php echo count($all_campaigns) !== 1 ? 's' : ''; ?> Joined</p>
      </div>
      <div class="card text-center">
        <h3 class="mt-0" style="font-size:2rem;color:var(--accent)"><?php echo count($all_reviews); ?></h3>
        <p class="mb-0">Review<?php echo count($all_reviews) !== 1 ? 's' : ''; ?> Submitted</p>
      </div>
      <div class="card text-center">
        <h3 class="mt-0" style="font-size:2rem;color:var(--accent)"><?php echo htmlspecialchars(get_tier_short((int)$arc_member['tier']), ENT_QUOTES, 'UTF-8'); ?></h3>
        <p class="mb-0">Current Tier</p>
      </div>
    </div>
  </section>

  <!-- Campaign History -->
  <div class="divider-gear"></div>
  <section class="section">
    <p class="section-label">Campaign History</p>
    <h2>All Campaigns</h2>
    <?php if (count($all_campaigns) > 0): ?>
      <div class="campaign-list">
        <?php foreach ($all_campaigns as $c): ?>
          <div class="campaign-card <?php echo $c['status'] === 'closed' ? 'campaign-card--closed' : ''; ?>">
            <div class="campaign-card__info">
              <h3><?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p>
                Invited: <?php echo htmlspecialchars($c['invited_at'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!empty($c['accepted_at'])): ?>
                  &middot; Accepted: <?php echo htmlspecialchars($c['accepted_at'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
              </p>
              <span class="campaign-status campaign-status--<?php echo htmlspecialchars($c['invite_status'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo ucfirst(htmlspecialchars($c['invite_status'], ENT_QUOTES, 'UTF-8')); ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No campaign history yet.</p>
    <?php endif; ?>
  </section>

  <!-- Review History -->
  <div class="divider-gear"></div>
  <section class="section">
    <p class="section-label">Review History</p>
    <h2>All Reviews</h2>
    <?php if (count($all_reviews) > 0): ?>
      <div class="review-list">
        <?php foreach ($all_reviews as $r): ?>
          <div class="review-list-item">
            <div>
              <strong><?php echo htmlspecialchars($r['campaign_title'], ENT_QUOTES, 'UTF-8'); ?></strong>
              <span class="review-list-item__platform"><?php echo ucfirst(htmlspecialchars($r['platform'], ENT_QUOTES, 'UTF-8')); ?></span>
              <?php if ($r['verified']): ?>
                <span style="color:var(--success);font-weight:600">&#10003; Verified</span>
              <?php endif; ?>
            </div>
            <span class="review-list-item__date"><?php echo htmlspecialchars($r['submitted_at'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No reviews submitted yet.</p>
    <?php endif; ?>
  </section>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
