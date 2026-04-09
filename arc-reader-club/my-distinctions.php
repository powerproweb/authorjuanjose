<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/member-auth.php';

$member_tier   = (int)$arc_member['tier'];
$review_count  = (int)$arc_member['review_count'];
$tier_progress = get_tier_progress($member_tier, $review_count);

// Distinction history
$stmt = $pdo->prepare('SELECT * FROM distinctions WHERE member_id = ? ORDER BY earned_at DESC');
$stmt->execute([$arc_member['id']]);
$distinction_history = $stmt->fetchAll();

$page_title = 'My Distinctions | ARC Reader Club';
$show_arc_sub_navigation = true;
$show_member_navigation = true;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>My Distinctions</h1>
  <p class="lead">Your tier progress and earned honors within the ARC Reader Club.</p>

  <hr class="ornament-rule">

  <!-- Current Tier -->
  <section class="section">
    <div class="dash-tier">
      <div class="tier-icon tier-icon--<?php echo get_tier_css_class($member_tier); ?> dash-tier__icon">&#9881;</div>
      <div class="dash-tier__info">
        <p class="section-label"><?php echo $member_tier > 0 ? 'Current Tier — Tier ' . $member_tier : 'No Tier Yet'; ?></p>
        <h2 class="mt-0"><?php echo htmlspecialchars(get_tier_name($member_tier), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p><?php echo $review_count; ?> review<?php echo $review_count !== 1 ? 's' : ''; ?> submitted</p>

        <?php if ($tier_progress !== null): ?>
          <div class="progress-bar">
            <div class="progress-bar__fill progress-bar__fill--<?php echo get_tier_css_class($member_tier); ?>" style="width:<?php echo $tier_progress['progress_pct']; ?>%"></div>
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

  <!-- All Tiers -->
  <section class="section">
    <p class="section-label">The Distinction Tiers</p>
    <h2>Your Journey</h2>
    <div class="tier-grid">
      <?php for ($t = 1; $t <= 4; $t++): ?>
        <?php $css = get_tier_css_class($t); $earned = $member_tier >= $t; ?>
        <div class="tier-card tier-card--<?php echo $css; ?> <?php echo $earned ? 'tier-card--earned' : 'tier-card--locked'; ?>">
          <div class="tier-icon tier-icon--<?php echo $css; ?>">&#9881;</div>
          <p class="tier-rank">Tier <?php echo $t; ?></p>
          <h3><?php echo htmlspecialchars(get_tier_name($t), ENT_QUOTES, 'UTF-8'); ?></h3>
          <p><?php echo get_tier_thresholds()[$t]; ?>+ reviews</p>
          <?php if ($earned): ?>
            <p style="color:var(--success);font-weight:700">&#10003; Earned</p>
          <?php elseif ($t === $member_tier + 1): ?>
            <p style="color:var(--accent);font-weight:600">In progress</p>
          <?php else: ?>
            <p style="color:var(--ink-light)">Locked</p>
          <?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>
  </section>

  <!-- History -->
  <?php if (count($distinction_history) > 0): ?>
    <div class="divider-gear"></div>
    <section class="section">
      <p class="section-label">Achievement Log</p>
      <h2>Distinction History</h2>
      <div class="review-list">
        <?php foreach ($distinction_history as $d): ?>
          <div class="review-list-item">
            <div>
              <strong><?php echo htmlspecialchars(get_tier_name((int)$d['tier']), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <span class="review-list-item__date">Earned: <?php echo htmlspecialchars($d['earned_at'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
