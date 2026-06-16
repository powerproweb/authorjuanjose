<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
ajj_require_admin_auth();
if (!defined('SITE_AUTH_GATE_ENABLED')) {
    define('SITE_AUTH_GATE_ENABLED', false);
}
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/db.php';

$pdo = get_db();
$actor = mailing_get_admin_actor_label();
$message = '';
$error = '';

$lists = get_active_mailing_lists($pdo);
$activeListById = [];
foreach ($lists as $list) {
    $activeListById[(int)$list['id']] = $list;
}

$normalizeContactIdsCsv = static function (string $raw): array {
    $parts = preg_split('/[,\s]+/', trim($raw)) ?: [];
    $ids = [];
    foreach ($parts as $part) {
        $contactId = (int)$part;
        if ($contactId > 0) {
            $ids[$contactId] = true;
        }
    }
    return array_keys($ids);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!csrf_validate('mailing_admin_actions', $csrfToken)) {
        $error = 'Your session token is invalid or expired. Refresh and try again.';
    } else {
        try {
            if ($action === 'save_memberships') {
                $contactId = (int)($_POST['contact_id'] ?? 0);
                $selectedListIds = $_POST['list_ids'] ?? [];
                if (!is_array($selectedListIds)) {
                    $selectedListIds = [];
                }
                mailing_set_contact_memberships($pdo, $contactId, $selectedListIds, 'admin_membership_update', $actor);
                $message = 'List memberships updated.';
            } elseif ($action === 'move_contact_list') {
                $contactId = (int)($_POST['contact_id'] ?? 0);
                $fromListId = (int)($_POST['from_list_id'] ?? 0);
                $toListId = (int)($_POST['to_list_id'] ?? 0);
                mailing_move_contact_between_lists($pdo, $contactId, $fromListId, $toListId, $actor);
                $message = 'Contact moved between lists.';
            } elseif ($action === 'copy_contact_list') {
                $contactId = (int)($_POST['contact_id'] ?? 0);
                $toListId = (int)($_POST['to_list_id'] ?? 0);
                mailing_copy_contact_to_list($pdo, $contactId, $toListId, $actor);
                $message = 'Contact copied to selected list.';
            } elseif ($action === 'unsubscribe_contact_list') {
                $contactId = (int)($_POST['contact_id'] ?? 0);
                $listId = (int)($_POST['list_id'] ?? 0);
                mailing_unsubscribe_contact_from_list($pdo, $contactId, $listId, $actor);
                $message = 'Contact unsubscribed from selected list.';
            } elseif ($action === 'bulk_membership_action') {
                $bulkAction = trim((string)($_POST['bulk_action'] ?? ''));
                $contactIds = $normalizeContactIdsCsv((string)($_POST['contact_ids_csv'] ?? ''));
                $fromListId = (int)($_POST['from_list_id'] ?? 0);
                $toListId = (int)($_POST['to_list_id'] ?? 0);
                $processed = mailing_bulk_apply_action($pdo, $contactIds, $bulkAction, $fromListId, $toListId, $actor);
                $message = sprintf('Bulk action complete. %d contact(s) updated.', $processed);
            } else {
                $error = 'Unsupported action.';
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$filterSlug = strtolower(trim((string)($_GET['list'] ?? 'all')));
$queryText = trim((string)($_GET['q'] ?? ''));
$wantsExportCsv = strtolower(trim((string)($_GET['export'] ?? ''))) === 'csv';

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

if ($wantsExportCsv) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="mailing-contacts-export-' . gmdate('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'wb');
    if ($out !== false) {
        fputcsv($out, ['contact_id', 'email', 'name', 'contact_status', 'active_lists', 'source', 'created_at', 'updated_at']);
        foreach ($contacts as $contact) {
            $contactId = (int)$contact['id'];
            $statusByList = $membershipMap[$contactId] ?? [];
            $activeListNames = [];
            foreach ($lists as $list) {
                $listId = (int)$list['id'];
                if (($statusByList[$listId] ?? '') === 'active') {
                    $activeListNames[] = (string)$list['name'];
                }
            }
            fputcsv($out, [
                $contactId,
                (string)$contact['email'],
                (string)$contact['name'],
                (string)$contact['status'],
                implode('; ', $activeListNames),
                (string)$contact['source'],
                (string)$contact['created_at'],
                (string)$contact['updated_at'],
            ]);
        }
        fclose($out);
    }
    exit;
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
$statsIgnoredDuplicateClusters = (int)$pdo->query('SELECT COUNT(*) FROM mailing_duplicate_ignored_clusters')->fetchColumn();

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

$csrfToken = csrf_token('mailing_admin_actions');
$page_title = 'Mailing Lists | Admin';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">
  <h1>Mailing Lists</h1>
  <p>
    <a href="/admin">&larr; Admin Dashboard</a>
    &middot;
    <a href="/admin/mailing-duplicates">Manage Duplicates</a>
  </p>

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
      <p class="mb-0" style="font-size:.82rem;color:var(--ink-light);"><?php echo $statsIgnoredDuplicateClusters; ?> ignored clusters</p>
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
    <div style="display:flex;gap:var(--space-xs);flex-wrap:wrap;">
      <button class="button" type="submit">Apply Filters</button>
      <a class="button button--outline" href="/admin/mailing-lists">Reset</a>
      <button class="button button--outline" type="submit" name="export" value="csv">Export CSV</button>
    </div>
  </form>

  <form id="bulk-actions-form" method="post" class="panel">
    <input type="hidden" name="action" value="bulk_membership_action">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <input id="bulk-contact-ids" type="hidden" name="contact_ids_csv" value="">
    <h2 class="mt-0">Bulk Membership Actions</h2>
    <p class="mb-0" style="font-size:.9rem;color:var(--ink-light);">Select contacts below, then apply one action.</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:var(--space-sm);margin-top:var(--space-sm);">
      <div class="form-group">
        <label for="bulk-action">Action</label>
        <select id="bulk-action" name="bulk_action" required>
          <option value="">Select action</option>
          <option value="move">Move (unsubscribe from From List, subscribe to To List)</option>
          <option value="copy">Copy (subscribe to To List)</option>
          <option value="unsubscribe">Unsubscribe from From List</option>
        </select>
      </div>
      <div class="form-group">
        <label for="bulk-from-list">From List</label>
        <select id="bulk-from-list" name="from_list_id">
          <option value="0">None</option>
          <?php foreach ($lists as $list): ?>
            <option value="<?php echo (int)$list['id']; ?>"><?php echo htmlspecialchars((string)$list['name'], ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="bulk-to-list">To List</label>
        <select id="bulk-to-list" name="to_list_id">
          <option value="0">None</option>
          <?php foreach ($lists as $list): ?>
            <option value="<?php echo (int)$list['id']; ?>"><?php echo htmlspecialchars((string)$list['name'], ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div style="display:flex;gap:var(--space-xs);align-items:center;margin-top:var(--space-xs);">
      <button class="button button--outline" type="submit">Run Bulk Action</button>
      <span id="bulk-selection-count" style="font-size:.85rem;color:var(--ink-light);">0 contacts selected</span>
    </div>
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
              <label style="display:flex;gap:.5rem;align-items:flex-start;">
                <input class="bulk-contact-checkbox" type="checkbox" value="<?php echo $contactId; ?>">
                <span>
                  <strong><?php echo htmlspecialchars((string)$contact['name'] !== '' ? (string)$contact['name'] : '(No name)', ENT_QUOTES, 'UTF-8'); ?></strong>
                  <span class="admin-list-item__email"><?php echo htmlspecialchars((string)$contact['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
              </label>
              <span class="campaign-status campaign-status--<?php echo htmlspecialchars((string)$contact['status'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo ucfirst(htmlspecialchars((string)$contact['status'], ENT_QUOTES, 'UTF-8')); ?>
              </span>
              <span class="admin-list-item__meta">
                Added <?php echo htmlspecialchars((string)$contact['created_at'], ENT_QUOTES, 'UTF-8'); ?>
                &middot; Source: <?php echo htmlspecialchars((string)$contact['source'], ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </div>
            <div class="admin-list-item__actions" style="max-width:740px;width:100%;">
              <form method="post" style="width:100%;margin-bottom:var(--space-xs);">
                <input type="hidden" name="action" value="save_memberships">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="contact_id" value="<?php echo $contactId; ?>">
                <fieldset style="border:1px solid var(--border);border-radius:var(--radius-md);padding:var(--space-sm);margin:0;">
                  <legend style="padding:0 var(--space-xs);font-size:.82rem;">Direct List Memberships</legend>
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

              <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--space-xs);">
                <form method="post" style="border:1px solid var(--border);border-radius:var(--radius-md);padding:var(--space-xs);">
                  <input type="hidden" name="action" value="move_contact_list">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="contact_id" value="<?php echo $contactId; ?>">
                  <strong style="font-size:.82rem;display:block;margin-bottom:.25rem;">Move</strong>
                  <label style="font-size:.78rem;">From</label>
                  <select name="from_list_id" required>
                    <option value="">Select list</option>
                    <?php foreach ($lists as $list): ?>
                      <option value="<?php echo (int)$list['id']; ?>"><?php echo htmlspecialchars((string)$list['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <label style="font-size:.78rem;">To</label>
                  <select name="to_list_id" required>
                    <option value="">Select list</option>
                    <?php foreach ($lists as $list): ?>
                      <option value="<?php echo (int)$list['id']; ?>"><?php echo htmlspecialchars((string)$list['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="button button--outline" type="submit" style="margin-top:.35rem;">Move</button>
                </form>

                <form method="post" style="border:1px solid var(--border);border-radius:var(--radius-md);padding:var(--space-xs);">
                  <input type="hidden" name="action" value="copy_contact_list">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="contact_id" value="<?php echo $contactId; ?>">
                  <strong style="font-size:.82rem;display:block;margin-bottom:.25rem;">Copy</strong>
                  <label style="font-size:.78rem;">To</label>
                  <select name="to_list_id" required>
                    <option value="">Select list</option>
                    <?php foreach ($lists as $list): ?>
                      <option value="<?php echo (int)$list['id']; ?>"><?php echo htmlspecialchars((string)$list['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="button button--outline" type="submit" style="margin-top:.35rem;">Copy</button>
                </form>

                <form method="post" style="border:1px solid var(--border);border-radius:var(--radius-md);padding:var(--space-xs);">
                  <input type="hidden" name="action" value="unsubscribe_contact_list">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="contact_id" value="<?php echo $contactId; ?>">
                  <strong style="font-size:.82rem;display:block;margin-bottom:.25rem;">Unsubscribe from List</strong>
                  <label style="font-size:.78rem;">List</label>
                  <select name="list_id" required>
                    <option value="">Select list</option>
                    <?php foreach ($lists as $list): ?>
                      <option value="<?php echo (int)$list['id']; ?>"><?php echo htmlspecialchars((string)$list['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="button button--outline" type="submit" style="margin-top:.35rem;">Unsubscribe</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<script>
  (function () {
    const bulkForm = document.getElementById('bulk-actions-form');
    const hiddenIds = document.getElementById('bulk-contact-ids');
    const countText = document.getElementById('bulk-selection-count');
    const checkboxes = Array.from(document.querySelectorAll('.bulk-contact-checkbox'));
    if (!bulkForm || !hiddenIds || !countText || checkboxes.length === 0) {
      return;
    }

    const refreshSelectedIds = () => {
      const ids = checkboxes.filter((cb) => cb.checked).map((cb) => cb.value);
      hiddenIds.value = ids.join(',');
      countText.textContent = ids.length + ' contacts selected';
      return ids;
    };

    checkboxes.forEach((checkbox) => {
      checkbox.addEventListener('change', refreshSelectedIds);
    });

    bulkForm.addEventListener('submit', (event) => {
      const ids = refreshSelectedIds();
      if (ids.length === 0) {
        event.preventDefault();
        alert('Select at least one contact for bulk actions.');
      }
    });

    refreshSelectedIds();
  })();
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
