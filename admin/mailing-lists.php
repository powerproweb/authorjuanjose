<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
ajj_require_admin_auth();
if (!defined('SITE_AUTH_GATE_ENABLED')) {
    define('SITE_AUTH_GATE_ENABLED', false);
}
require_once dirname(__DIR__) . '/includes/db.php';

$pdo = get_db();
$message = '';
$error = '';

$lists = get_active_mailing_lists($pdo);
$activeListById = [];
foreach ($lists as $list) {
    $activeListById[(int)$list['id']] = $list;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'save_memberships') {
    $contactId = (int)($_POST['contact_id'] ?? 0);
    $selectedListIds = $_POST['list_ids'] ?? [];
    if (!is_array($selectedListIds)) {
        $selectedListIds = [];
    }

    $selectedMap = [];
    foreach ($selectedListIds as $listIdRaw) {
        $listId = (int)$listIdRaw;
        if ($listId > 0 && isset($activeListById[$listId])) {
            $selectedMap[$listId] = true;
        }
    }

    $contactExists = false;
    if ($contactId > 0) {
        $contactCheck = $pdo->prepare('SELECT COUNT(*) FROM mailing_contacts WHERE id = ?');
        $contactCheck->execute([$contactId]);
        $contactExists = (int)$contactCheck->fetchColumn() > 0;
    }

    if (!$contactExists) {
        $error = 'Contact not found.';
    } else {
        $reactivateMembership = $pdo->prepare('
            UPDATE mailing_list_memberships
            SET status = "active", unsubscribed_at = NULL, updated_at = CURRENT_TIMESTAMP
            WHERE contact_id = ? AND list_id = ?
        ');
        $insertMembership = $pdo->prepare('
            INSERT OR IGNORE INTO mailing_list_memberships (contact_id, list_id, status, source)
            VALUES (?, ?, "active", "admin_membership_update")
        ');
        $unsubscribeMembership = $pdo->prepare('
            UPDATE mailing_list_memberships
            SET status = "unsubscribed", unsubscribed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
            WHERE contact_id = ? AND list_id = ? AND status != "unsubscribed"
        ');

        foreach (array_keys($activeListById) as $listId) {
            if (isset($selectedMap[$listId])) {
                $reactivateMembership->execute([$contactId, $listId]);
                $insertMembership->execute([$contactId, $listId]);
            } else {
                $unsubscribeMembership->execute([$contactId, $listId]);
            }
        }

        $contactStatus = $selectedMap === [] ? 'unsubscribed' : 'active';
        $pdo->prepare('UPDATE mailing_contacts SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
            ->execute([$contactStatus, $contactId]);

        $message = 'List memberships updated.';
    }
}

$filterSlug = strtolower(trim((string)($_GET['list'] ?? 'all')));
$queryText = trim((string)($_GET['q'] ?? ''));

$filterListId = 0;
if ($filterSlug !== '' && $filterSlug !== 'all') {
    foreach ($lists as $list) {
        if (strtolower((string)$list['slug']) === $filterSlug) {
            $filterListId = (int)$list['id'];
            break;
        }
    }
}

$where = [];
$params = [];
if ($queryText !== '') {
    $where[] = '(mc.email LIKE ? OR mc.name LIKE ?)';
    $params[] = '%' . $queryText . '%';
    $params[] = '%' . $queryText . '%';
}
if ($filterListId > 0) {
    $where[] = 'EXISTS (
        SELECT 1
        FROM mailing_list_memberships mlm
        WHERE mlm.contact_id = mc.id AND mlm.list_id = ? AND mlm.status = "active"
    )';
    $params[] = $filterListId;
}

$whereSql = $where === [] ? '' : ('WHERE ' . implode(' AND ', $where));
$contactsStmt = $pdo->prepare("
    SELECT mc.id, mc.email, mc.name, mc.status, mc.source, mc.created_at, mc.updated_at
    FROM mailing_contacts mc
    {$whereSql}
    ORDER BY mc.created_at DESC
    LIMIT 250
");
$contactsStmt->execute($params);
$contacts = $contactsStmt->fetchAll();

$contactIds = array_map(static fn(array $row): int => (int)$row['id'], $contacts);
$membershipMap = [];

if ($contactIds !== []) {
    $inPlaceholders = implode(',', array_fill(0, count($contactIds), '?'));
    $membershipStmt = $pdo->prepare("
        SELECT contact_id, list_id, status
        FROM mailing_list_memberships
        WHERE contact_id IN ({$inPlaceholders})
    ");
    $membershipStmt->execute($contactIds);
    foreach ($membershipStmt->fetchAll() as $membershipRow) {
        $contactId = (int)$membershipRow['contact_id'];
        $listId = (int)$membershipRow['list_id'];
        $membershipMap[$contactId][$listId] = (string)$membershipRow['status'];
    }
}

$statsTotalContacts = (int)$pdo->query('SELECT COUNT(*) FROM mailing_contacts')->fetchColumn();
$statsActiveContacts = (int)$pdo->query('SELECT COUNT(*) FROM mailing_contacts WHERE status = "active"')->fetchColumn();
$statsActiveMemberships = (int)$pdo->query('SELECT COUNT(*) FROM mailing_list_memberships WHERE status = "active"')->fetchColumn();
$statsDuplicateEmails = (int)$pdo->query('
    SELECT COUNT(*)
    FROM (
        SELECT LOWER(TRIM(email)) AS normalized_email, COUNT(*) AS c
        FROM mailing_contacts
        GROUP BY normalized_email
        HAVING COUNT(*) > 1
    ) dupes
')->fetchColumn();

$listCountsStmt = $pdo->query('
    SELECT
        ml.id,
        ml.slug,
        ml.name,
        ml.sort_order,
        COUNT(CASE WHEN mlm.status = "active" THEN 1 END) AS active_contacts
    FROM mailing_lists ml
    LEFT JOIN mailing_list_memberships mlm ON mlm.list_id = ml.id
    WHERE ml.is_active = 1
    GROUP BY ml.id, ml.slug, ml.name, ml.sort_order
    ORDER BY ml.sort_order ASC, ml.id ASC
');
$listCounts = $listCountsStmt->fetchAll();

$page_title = 'Mailing Lists | Admin';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">
  <h1>Mailing Lists</h1>
  <p><a href="/admin">&larr; Admin Dashboard</a></p>

  <?php if ($message !== ''): ?>
    <div class="alert alert--success" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <div class="card-grid">
    <div class="card text-center">
      <h3 class="mt-0" style="font-size:2rem;color:var(--accent)"><?php echo $statsTotalContacts; ?></h3>
      <p class="mb-0">Total Contacts</p>
    </div>
    <div class="card text-center">
      <h3 class="mt-0" style="font-size:2rem;color:var(--success)"><?php echo $statsActiveContacts; ?></h3>
      <p class="mb-0">Active Contacts</p>
    </div>
    <div class="card text-center">
      <h3 class="mt-0" style="font-size:2rem;color:var(--accent)"><?php echo $statsActiveMemberships; ?></h3>
      <p class="mb-0">Active Memberships</p>
    </div>
    <div class="card text-center">
      <h3 class="mt-0" style="font-size:2rem;color:<?php echo $statsDuplicateEmails > 0 ? 'var(--danger)' : 'var(--success)'; ?>">
        <?php echo $statsDuplicateEmails; ?>
      </h3>
      <p class="mb-0">Duplicate Emails</p>
    </div>
  </div>

  <div class="divider-gear"></div>

  <form method="get" class="panel">
    <div class="form-group">
      <label for="q">Search by email or name</label>
      <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($queryText, ENT_QUOTES, 'UTF-8'); ?>" placeholder="example@author.com">
    </div>
    <div class="form-group">
      <label for="list">Filter by list</label>
      <select id="list" name="list">
        <option value="all"<?php echo $filterSlug === 'all' ? ' selected' : ''; ?>>All Lists</option>
        <?php foreach ($listCounts as $list): ?>
          <?php $slug = strtolower((string)$list['slug']); ?>
          <option value="<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filterSlug === $slug ? ' selected' : ''; ?>>
            <?php echo htmlspecialchars((string)$list['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int)$list['active_contacts']; ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="button" type="submit">Apply Filters</button>
    <a class="button button--outline" href="/admin/mailing-lists">Reset</a>
  </form>

  <section class="section">
    <h2>Contacts</h2>
    <?php if ($contacts === []): ?>
      <p>No contacts match this filter.</p>
    <?php else: ?>
      <div class="admin-list">
        <?php foreach ($contacts as $contact): ?>
          <?php
          $contactId = (int)$contact['id'];
          $statusByList = $membershipMap[$contactId] ?? [];
          ?>
          <div class="admin-list-item">
            <div class="admin-list-item__info">
              <strong><?php echo htmlspecialchars((string)$contact['name'] !== '' ? (string)$contact['name'] : '(No name)', ENT_QUOTES, 'UTF-8'); ?></strong>
              <span class="admin-list-item__email"><?php echo htmlspecialchars((string)$contact['email'], ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="campaign-status campaign-status--<?php echo htmlspecialchars((string)$contact['status'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo ucfirst(htmlspecialchars((string)$contact['status'], ENT_QUOTES, 'UTF-8')); ?>
              </span>
              <span class="admin-list-item__meta">
                Added <?php echo htmlspecialchars((string)$contact['created_at'], ENT_QUOTES, 'UTF-8'); ?>
                &middot; Source: <?php echo htmlspecialchars((string)$contact['source'], ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </div>
            <div class="admin-list-item__actions" style="max-width:620px;width:100%;">
              <form method="post" style="width:100%;">
                <input type="hidden" name="action" value="save_memberships">
                <input type="hidden" name="contact_id" value="<?php echo $contactId; ?>">
                <fieldset style="border:1px solid var(--border);border-radius:var(--radius-md);padding:var(--space-sm);margin:0;">
                  <legend style="padding:0 var(--space-xs);font-size:.82rem;">Lists</legend>
                  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:var(--space-xs);">
                    <?php foreach ($lists as $list): ?>
                      <?php
                      $listId = (int)$list['id'];
                      $isChecked = ($statusByList[$listId] ?? '') === 'active';
                      ?>
                      <label style="display:flex;gap:.4rem;align-items:flex-start;">
                        <input type="checkbox" name="list_ids[]" value="<?php echo $listId; ?>"<?php echo $isChecked ? ' checked' : ''; ?>>
                        <span style="font-size:.85rem;"><?php echo htmlspecialchars((string)$list['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </fieldset>
                <div style="margin-top:var(--space-xs);display:flex;gap:var(--space-xs);justify-content:flex-end;">
                  <button class="button button--outline" type="submit">Save Lists</button>
                </div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
