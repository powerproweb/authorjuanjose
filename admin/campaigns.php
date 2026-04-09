<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

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

        if ($title !== '') {
            $stmt = $pdo->prepare('INSERT INTO campaigns (title, book_slug, description, arc_format, review_deadline, status) VALUES (?, ?, ?, ?, ?, "draft")');
            $stmt->execute([$title, $book, $desc, $format, $deadline !== '' ? $deadline : null]);
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
        // Invite all active members who aren't already invited
        $stmt = $pdo->prepare('
            INSERT OR IGNORE INTO campaign_invites (campaign_id, member_id)
            SELECT ?, id FROM members WHERE status = "active"
            AND id NOT IN (SELECT member_id FROM campaign_invites WHERE campaign_id = ?)
        ');
        $stmt->execute([$id, $id]);
        $message = $stmt->rowCount() . ' member(s) invited.';
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
                <?php echo (int)$c['review_count']; ?> reviews
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
                  <button class="button button--outline" type="submit" name="action" value="invite_all">Invite All Members</button>
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
