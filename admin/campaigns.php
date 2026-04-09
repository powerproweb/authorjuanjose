<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/email-templates.php';

$pdo = get_db();
$message = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create') {
        $title    = trim((string)($_POST['title'] ?? ''));
        $desc     = trim((string)($_POST['description'] ?? ''));
        $book     = trim((string)($_POST['book_slug'] ?? ''));
        $format   = (string)($_POST['arc_format'] ?? 'ebook');
        $deadline = trim((string)($_POST['review_deadline'] ?? ''));
        $lang     = (string)($_POST['language'] ?? 'both');
        if (!in_array($lang, ['English', 'Spanish', 'both'], true)) $lang = 'both';

        if ($title !== '') {
            $stmt = $pdo->prepare('INSERT INTO campaigns (title, book_slug, description, arc_format, review_deadline, language, status) VALUES (?, ?, ?, ?, ?, ?, "draft")');
            $stmt->execute([$title, $book, $desc, $format, $deadline !== '' ? $deadline : null, $lang]);
            $message = 'Campaign "' . $title . '" created as draft.';
        }
    } elseif ($action === 'activate') {
        $id = (int)($_POST['campaign_id'] ?? 0);
        $pdo->prepare('UPDATE campaigns SET status = "active" WHERE id = ? AND status = "draft"')->execute([$id]);
        $message = 'Campaign activated.';
    } elseif ($action === 'close') {
        $id = (int)($_POST['campaign_id'] ?? 0);
        $pdo->prepare('UPDATE campaigns SET status = "closed" WHERE id = ?')->execute([$id]);
        $message = 'Campaign closed.';
    } elseif ($action === 'invite_all') {
        $id = (int)($_POST['campaign_id'] ?? 0);
        // Get campaign language to filter members
        $camp = $pdo->prepare('SELECT language, title, review_deadline FROM campaigns WHERE id = ?');
        $camp->execute([$id]);
        $campData = $camp->fetch();
        $camp_lang = $campData['language'] ?? 'both';

        // Build member filter based on campaign language
        if ($camp_lang === 'both') {
            $member_filter = 'status = "active"';
        } else {
            $member_filter = 'status = "active" AND language = "' . $camp_lang . '"';
        }

        $stmt = $pdo->prepare("
            INSERT OR IGNORE INTO campaign_invites (campaign_id, member_id)
            SELECT ?, id FROM members WHERE {$member_filter}
            AND id NOT IN (SELECT member_id FROM campaign_invites WHERE campaign_id = ?)
        ");
        $stmt->execute([$id, $id]);
        $message = $stmt->rowCount() . ' member(s) invited.';
    } elseif ($action === 'invite_and_notify') {
        $id = (int)($_POST['campaign_id'] ?? 0);
        $camp = $pdo->prepare('SELECT * FROM campaigns WHERE id = ?');
        $camp->execute([$id]);
        $campData = $camp->fetch();

        if ($campData) {
            $camp_lang = $campData['language'] ?? 'both';
            if ($camp_lang === 'both') {
                $member_filter = 'status = "active"';
            } else {
                $member_filter = 'status = "active" AND language = "' . $camp_lang . '"';
            }

            // Invite
            $pdo->prepare("
                INSERT OR IGNORE INTO campaign_invites (campaign_id, member_id)
                SELECT ?, id FROM members WHERE {$member_filter}
                AND id NOT IN (SELECT member_id FROM campaign_invites WHERE campaign_id = ?)
            ")->execute([$id, $id]);

            // Get all invited members for this campaign to send notifications
            $invited = $pdo->prepare('
                SELECT m.id, m.name, m.email, m.language
                FROM campaign_invites ci
                JOIN members m ON m.id = ci.member_id
                WHERE ci.campaign_id = ? AND ci.status = "invited"
            ');
            $invited->execute([$id]);
            $invitedMembers = $invited->fetchAll();

            $queued = 0;
            foreach ($invitedMembers as $m) {
                // Queue email
                $pdo->prepare('INSERT INTO email_queue (recipient_email, recipient_name, template_key, language) VALUES (?, ?, ?, ?)')
                    ->execute([$m['email'], $m['name'], 'campaign_invite', $m['language']]);
                // Create notification
                $pdo->prepare('INSERT INTO notifications (member_id, type, message) VALUES (?, "invite", ?)')
                    ->execute([$m['id'], 'New campaign: ' . $campData['title']]);
                $queued++;
            }
            $message = $queued . ' member(s) invited and notified.';
        }
    }
}

// Load campaigns
$campaigns = $pdo->query('
    SELECT c.*,
        (SELECT COUNT(*) FROM campaign_invites WHERE campaign_id = c.id) AS invite_count,
        (SELECT COUNT(*) FROM reviews WHERE campaign_id = c.id) AS review_count
    FROM campaigns c
    ORDER BY
        CASE c.status WHEN "active" THEN 0 WHEN "draft" THEN 1 ELSE 2 END,
        c.created_at DESC
')->fetchAll();

$page_title = 'Manage Campaigns | Admin';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Manage Campaigns</h1>
  <p><a href="/admin">&larr; Admin Dashboard</a></p>

  <?php if ($message !== ''): ?>
    <div class="alert alert--success" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <!-- Create Campaign -->
  <section class="section">
    <h2>Create New Campaign</h2>
    <form method="post" class="panel">
      <input type="hidden" name="action" value="create">
      <div class="form-group">
        <label for="title">Campaign Title <span class="text-accent">*</span></label>
        <input id="title" name="title" type="text" required placeholder="e.g. Michael Strogoff Launch ARC">
      </div>
      <div class="form-group">
        <label for="book_slug">Book Slug</label>
        <input id="book_slug" name="book_slug" type="text" placeholder="e.g. michael-strogoff">
      </div>
      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" placeholder="Campaign details for members"></textarea>
      </div>
      <div class="form-group">
        <label for="arc_format">ARC Format</label>
        <select id="arc_format" name="arc_format">
          <option value="ebook">eBook</option>
          <option value="pdf">PDF</option>
          <option value="epub">ePub</option>
        </select>
      </div>
      <div class="form-group">
        <label for="review_deadline">Review Deadline</label>
        <input id="review_deadline" name="review_deadline" type="date">
      </div>
      <div class="form-group">
        <label for="language">Language</label>
        <select id="language" name="language">
          <option value="both">Both (English &amp; Spanish)</option>
          <option value="English">English Only</option>
          <option value="Spanish">Spanish Only</option>
        </select>
        <small>Members will be invited based on their language preference.</small>
      </div>
      <button class="button" type="submit">Create Campaign</button>
    </form>
  </section>

  <div class="divider-gear"></div>

  <!-- Campaign List -->
  <section class="section">
    <h2>All Campaigns</h2>
    <?php if (count($campaigns) > 0): ?>
      <div class="admin-list">
        <?php foreach ($campaigns as $c): ?>
          <div class="admin-list-item">
            <div class="admin-list-item__info">
              <strong><?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
              <span class="campaign-status campaign-status--<?php echo htmlspecialchars($c['status'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo ucfirst(htmlspecialchars($c['status'], ENT_QUOTES, 'UTF-8')); ?>
              </span>
              <span class="admin-list-item__meta">
                <?php echo (int)$c['invite_count']; ?> invited &middot;
                <?php echo (int)$c['review_count']; ?> reviews &middot;
                Lang: <?php echo htmlspecialchars($c['language'] ?? 'both', ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!empty($c['review_deadline'])): ?>
                  &middot; Deadline: <?php echo htmlspecialchars($c['review_deadline'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
              </span>
            </div>
            <div class="admin-list-item__actions">
              <form method="post" style="display:inline">
                <input type="hidden" name="campaign_id" value="<?php echo (int)$c['id']; ?>">
                <?php if ($c['status'] === 'draft'): ?>
                  <button class="button" type="submit" name="action" value="activate">Activate</button>
                <?php endif; ?>
                <?php if ($c['status'] === 'active'): ?>
                  <button class="button" type="submit" name="action" value="invite_and_notify">Invite &amp; Notify</button>
                  <button class="button button--outline" type="submit" name="action" value="invite_all">Invite Only</button>
                  <button class="button button--outline" type="submit" name="action" value="close">Close</button>
                <?php endif; ?>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No campaigns yet. Create one above.</p>
    <?php endif; ?>
  </section>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
