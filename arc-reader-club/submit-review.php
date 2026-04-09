<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/member-auth.php';

$errors  = [];
$success = '';
$promotion_msg = '';
$preselect_campaign = (int)($_GET['campaign'] ?? 0);

// Campaigns this member can review (accepted invites on active campaigns)
$stmt = $pdo->prepare('
    SELECT c.id, c.title
    FROM campaign_invites ci
    JOIN campaigns c ON c.id = ci.campaign_id
    WHERE ci.member_id = ? AND ci.status = "accepted" AND c.status = "active"
    ORDER BY c.title
');
$stmt->execute([$arc_member['id']]);
$available_campaigns = $stmt->fetchAll();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_id = (int)($_POST['campaign_id'] ?? 0);
    $platform    = (string)($_POST['platform'] ?? '');
    $review_url  = trim((string)($_POST['review_url'] ?? ''));
    $review_text = trim((string)($_POST['review_text'] ?? ''));

    // Validate
    if ($campaign_id < 1) {
        $errors[] = 'Please select a campaign.';
    }
    if (!in_array($platform, ['amazon', 'goodreads', 'other'], true)) {
        $errors[] = 'Please select a review platform.';
    }
    if ($review_url === '') {
        $errors[] = 'Please provide a link to your review.';
    }

    // Verify this member is invited and accepted
    if ($campaign_id > 0) {
        $stmt = $pdo->prepare('SELECT id FROM campaign_invites WHERE campaign_id = ? AND member_id = ? AND status = "accepted"');
        $stmt->execute([$campaign_id, $arc_member['id']]);
        if (!$stmt->fetch()) {
            $errors[] = 'You are not currently participating in this campaign.';
        }
    }

    if (count($errors) === 0) {
        // Insert review
        $stmt = $pdo->prepare('INSERT INTO reviews (member_id, campaign_id, platform, review_url, review_text) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$arc_member['id'], $campaign_id, $platform, $review_url, $review_text]);

        // Increment review count
        $pdo->prepare('UPDATE members SET review_count = review_count + 1 WHERE id = ?')->execute([$arc_member['id']]);

        // Mark invite as completed
        $pdo->prepare('UPDATE campaign_invites SET status = "completed" WHERE campaign_id = ? AND member_id = ?')
            ->execute([$campaign_id, $arc_member['id']]);

        // Check tier promotion
        $new_tier = check_tier_promotion($pdo, (int)$arc_member['id']);
        if ($new_tier > 0) {
            $promotion_msg = 'Congratulations! You have been promoted to ' . get_tier_name($new_tier) . '!';
        }

        $success = 'Your review has been submitted successfully. Thank you for your support!';

        // Refresh member data
        $stmt = $pdo->prepare('SELECT * FROM members WHERE id = ?');
        $stmt->execute([$arc_member['id']]);
        $arc_member = $stmt->fetch();

        // Refresh available campaigns
        $stmt = $pdo->prepare('
            SELECT c.id, c.title
            FROM campaign_invites ci
            JOIN campaigns c ON c.id = ci.campaign_id
            WHERE ci.member_id = ? AND ci.status = "accepted" AND c.status = "active"
            ORDER BY c.title
        ');
        $stmt->execute([$arc_member['id']]);
        $available_campaigns = $stmt->fetchAll();
    }
}

$page_title = 'Submit Review | ARC Reader Club';
$show_arc_sub_navigation = true;
$show_member_navigation = true;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">
  <div class="container--narrow" style="margin:0 auto">

    <h1>Submit a Review</h1>
    <p class="lead">Completed your ARC read? Submit your review below.</p>

    <hr class="ornament-rule">

    <?php if ($promotion_msg !== ''): ?>
      <div class="alert alert--success" role="status" style="background:#fdf6e3;border-color:var(--gold);color:var(--accent-dark)">
        &#127942; <?php echo htmlspecialchars($promotion_msg, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
      <div class="alert alert--success" role="status"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (count($errors) > 0): ?>
      <div class="alert alert--error" role="alert">
        <?php foreach ($errors as $e): ?>
          <p class="mb-0"><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (count($available_campaigns) > 0): ?>
      <form method="post" novalidate>

        <div class="form-group">
          <label for="campaign_id">Campaign <span class="text-accent">*</span></label>
          <select id="campaign_id" name="campaign_id" required>
            <option value="" disabled <?php echo $preselect_campaign === 0 ? 'selected' : ''; ?>>Select a campaign</option>
            <?php foreach ($available_campaigns as $c): ?>
              <option value="<?php echo (int)$c['id']; ?>"
                <?php echo $preselect_campaign === (int)$c['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="platform">Platform <span class="text-accent">*</span></label>
          <select id="platform" name="platform" required>
            <option value="amazon">Amazon</option>
            <option value="goodreads">Goodreads</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="form-group">
          <label for="review_url">Link to Your Review <span class="text-accent">*</span></label>
          <input id="review_url" name="review_url" type="url" required placeholder="https://...">
        </div>

        <div class="form-group">
          <label for="review_text">Review Text (optional)</label>
          <textarea id="review_text" name="review_text" placeholder="Paste or summarize your review here"></textarea>
        </div>

        <p class="mt-lg">
          <button class="button button--lg" type="submit">Submit Review</button>
        </p>

      </form>
    <?php else: ?>
      <section class="panel">
        <p>You have no active campaigns available for review. Accept a campaign invitation from your <a href="/arc-reader-club/current-missions">missions page</a> first.</p>
      </section>
    <?php endif; ?>

  </div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
