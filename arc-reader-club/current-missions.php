<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/member-auth.php';

// Handle accept/decline actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invite_id = (int)($_POST['invite_id'] ?? 0);
    $action    = (string)($_POST['action'] ?? '');

    if ($invite_id > 0 && in_array($action, ['accept', 'decline'], true)) {
        $new_status = $action === 'accept' ? 'accepted' : 'declined';
        $stmt = $pdo->prepare('
            UPDATE campaign_invites SET status = ?, accepted_at = CASE WHEN ? = "accepted" THEN datetime("now") ELSE accepted_at END
            WHERE id = ? AND member_id = ? AND status = "invited"
        ');
        $stmt->execute([$new_status, $new_status, $invite_id, $arc_member['id']]);
    }
    header('Location: /arc-reader-club/current-missions');
    exit;
}

// All campaigns for this member
$stmt = $pdo->prepare('
    SELECT c.*, ci.id AS invite_id, ci.status AS invite_status, ci.invited_at, ci.accepted_at
    FROM campaign_invites ci
    JOIN campaigns c ON c.id = ci.campaign_id
    WHERE ci.member_id = ?
    ORDER BY
        CASE c.status WHEN "active" THEN 0 WHEN "closed" THEN 1 ELSE 2 END,
        c.review_deadline ASC
');
$stmt->execute([$arc_member['id']]);
$missions = $stmt->fetchAll();

$page_title = 'Current Missions | ARC Reader Club';
$show_arc_sub_navigation = true;
$show_member_navigation = true;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Current Missions</h1>
  <p class="lead">Your ARC campaign invitations and active missions.</p>

  <hr class="ornament-rule">

  <?php if (count($missions) > 0): ?>
    <div class="campaign-list">
      <?php foreach ($missions as $m): ?>
        <div class="campaign-card <?php echo $m['status'] === 'closed' ? 'campaign-card--closed' : ''; ?>">
          <div class="campaign-card__info">
            <h3><?php echo htmlspecialchars($m['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php if (!empty($m['description'])): ?>
              <p><?php echo htmlspecialchars($m['description'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <?php if (!empty($m['review_deadline'])): ?>
              <p class="campaign-card__deadline">Review deadline: <?php echo htmlspecialchars($m['review_deadline'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <p>
              <span class="campaign-status campaign-status--<?php echo htmlspecialchars($m['invite_status'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo ucfirst(htmlspecialchars($m['invite_status'], ENT_QUOTES, 'UTF-8')); ?>
              </span>
              <span class="campaign-status campaign-status--<?php echo htmlspecialchars($m['status'], ENT_QUOTES, 'UTF-8'); ?>">
                Campaign: <?php echo ucfirst(htmlspecialchars($m['status'], ENT_QUOTES, 'UTF-8')); ?>
              </span>
            </p>
          </div>
          <div class="campaign-card__actions">
            <?php if ($m['invite_status'] === 'invited' && $m['status'] === 'active'): ?>
              <form method="post" style="display:inline">
                <input type="hidden" name="invite_id" value="<?php echo (int)$m['invite_id']; ?>">
                <button class="button" type="submit" name="action" value="accept">Accept</button>
                <button class="button button--outline" type="submit" name="action" value="decline">Decline</button>
              </form>
            <?php elseif ($m['invite_status'] === 'accepted' && $m['status'] === 'active'): ?>
              <a class="button" href="/arc-reader-club/submit-review?campaign=<?php echo (int)$m['id']; ?>">Submit Review</a>
            <?php elseif ($m['invite_status'] === 'completed'): ?>
              <span style="color:var(--success);font-weight:600">&#10003; Completed</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <section class="panel">
      <p>You have no campaign invitations yet. When new ARC campaigns are launched, your missions will appear here.</p>
    </section>
  <?php endif; ?>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
