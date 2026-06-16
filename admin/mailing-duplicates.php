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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!csrf_validate('mailing_duplicates_actions', $csrfToken)) {
        $error = 'Your session token is invalid or expired. Refresh and try again.';
    } else {
        try {
            if ($action === 'ignore_cluster') {
                $normalizedEmail = (string)($_POST['normalized_email'] ?? '');
                $reason = trim((string)($_POST['reason'] ?? ''));
                mailing_ignore_duplicate_cluster($pdo, $normalizedEmail, $actor, $reason);
                $message = 'Duplicate cluster ignored.';
            } elseif ($action === 'unignore_cluster') {
                $normalizedEmail = (string)($_POST['normalized_email'] ?? '');
                mailing_unignore_duplicate_cluster($pdo, $normalizedEmail);
                $message = 'Ignored cluster reopened.';
            } elseif ($action === 'merge_cluster') {
                $normalizedEmail = (string)($_POST['normalized_email'] ?? '');
                $winnerContactId = (int)($_POST['winner_contact_id'] ?? 0);
                $mergedContactIds = $_POST['merged_contact_ids'] ?? [];
                if (!is_array($mergedContactIds)) {
                    $mergedContactIds = [];
                }

                $mergeCount = 0;
                foreach ($mergedContactIds as $mergedContactIdRaw) {
                    $mergedContactId = (int)$mergedContactIdRaw;
                    if ($mergedContactId <= 0 || $mergedContactId === $winnerContactId) {
                        continue;
                    }
                    mailing_merge_contacts($pdo, $winnerContactId, $mergedContactId, $actor);
                    $mergeCount++;
                }

                if ($mergeCount === 0) {
                    throw new RuntimeException('Select at least one duplicate contact to merge.');
                }

                mailing_unignore_duplicate_cluster($pdo, $normalizedEmail);
                $message = sprintf('Merge complete. %d duplicate contact(s) merged.', $mergeCount);
            } else {
                $error = 'Unsupported duplicate action.';
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$clusters = mailing_get_duplicate_clusters($pdo, true);
$activeClusters = [];
$ignoredClusters = [];
foreach ($clusters as $cluster) {
    if (($cluster['is_ignored'] ?? false) === true) {
        $ignoredClusters[] = $cluster;
    } else {
        $activeClusters[] = $cluster;
    }
}

$csrfToken = csrf_token('mailing_duplicates_actions');
$page_title = 'Mailing Duplicates | Admin';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">
  <h1>Mailing Duplicate Queue</h1>
  <p>
    <a href="/admin">&larr; Admin Dashboard</a>
    &middot;
    <a href="/admin/mailing-lists">Back to Mailing Lists</a>
  </p>

  <?php if ($message !== ''): ?>
    <div class="alert alert--success" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <div class="card-grid">
    <div class="card text-center">
      <h3 class="mt-0" style="font-size:2rem;color:var(--danger)"><?php echo count($activeClusters); ?></h3>
      <p class="mb-0">Active Duplicate Clusters</p>
    </div>
    <div class="card text-center">
      <h3 class="mt-0" style="font-size:2rem;color:var(--accent)"><?php echo count($ignoredClusters); ?></h3>
      <p class="mb-0">Ignored Clusters</p>
    </div>
  </div>

  <div class="divider-gear"></div>

  <section class="section">
    <h2>Active Duplicate Clusters</h2>
    <?php if ($activeClusters === []): ?>
      <p>No active duplicate clusters were found.</p>
    <?php else: ?>
      <div class="admin-list">
        <?php foreach ($activeClusters as $cluster): ?>
          <?php
          $normalizedEmail = (string)$cluster['normalized_email'];
          $clusterContacts = is_array($cluster['contacts'] ?? null) ? $cluster['contacts'] : [];
          ?>
          <div class="admin-list-item">
            <div class="admin-list-item__info">
              <strong><?php echo htmlspecialchars($normalizedEmail, ENT_QUOTES, 'UTF-8'); ?></strong>
              <span class="admin-list-item__meta"><?php echo (int)$cluster['duplicate_count']; ?> records</span>
            </div>
            <div class="admin-list-item__actions" style="width:100%;max-width:980px;">
              <form method="post" style="border:1px solid var(--border);border-radius:var(--radius-md);padding:var(--space-sm);margin-bottom:var(--space-xs);">
                <input type="hidden" name="action" value="merge_cluster">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="normalized_email" value="<?php echo htmlspecialchars($normalizedEmail, ENT_QUOTES, 'UTF-8'); ?>">
                <h3 class="mt-0" style="font-size:1rem;">Merge Contacts</h3>
                <div class="form-group">
                  <label for="winner_<?php echo htmlspecialchars(md5($normalizedEmail), ENT_QUOTES, 'UTF-8'); ?>">Winner contact</label>
                  <select id="winner_<?php echo htmlspecialchars(md5($normalizedEmail), ENT_QUOTES, 'UTF-8'); ?>" name="winner_contact_id" required>
                    <option value="">Select winner</option>
                    <?php foreach ($clusterContacts as $contact): ?>
                      <?php $contactId = (int)$contact['id']; ?>
                      <option value="<?php echo $contactId; ?>">
                        #<?php echo $contactId; ?> - <?php echo htmlspecialchars((string)$contact['email'], ENT_QUOTES, 'UTF-8'); ?><?php echo trim((string)$contact['name']) !== '' ? ' (' . htmlspecialchars((string)$contact['name'], ENT_QUOTES, 'UTF-8') . ')' : ''; ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Contacts to merge into winner</label>
                  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:var(--space-xs);">
                    <?php foreach ($clusterContacts as $contact): ?>
                      <?php
                      $contactId = (int)$contact['id'];
                      $memberships = is_array($contact['memberships'] ?? null) ? $contact['memberships'] : [];
                      $activeMembershipNames = [];
                      foreach ($memberships as $membership) {
                          if ((string)($membership['status'] ?? '') === 'active') {
                              $activeMembershipNames[] = (string)($membership['list_name'] ?? '');
                          }
                      }
                      ?>
                      <label style="display:flex;gap:.5rem;align-items:flex-start;border:1px solid var(--border);border-radius:var(--radius-sm);padding:.45rem;">
                        <input type="checkbox" name="merged_contact_ids[]" value="<?php echo $contactId; ?>">
                        <span>
                          <strong>#<?php echo $contactId; ?> <?php echo htmlspecialchars((string)$contact['email'], ENT_QUOTES, 'UTF-8'); ?></strong>
                          <?php if (trim((string)$contact['name']) !== ''): ?>
                            <small style="display:block;"><?php echo htmlspecialchars((string)$contact['name'], ENT_QUOTES, 'UTF-8'); ?></small>
                          <?php endif; ?>
                          <small style="display:block;">
                            Status: <?php echo htmlspecialchars((string)$contact['status'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ($activeMembershipNames !== []): ?>
                              &middot; Active lists: <?php echo htmlspecialchars(implode(', ', $activeMembershipNames), ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                          </small>
                        </span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </div>
                <button class="button button--outline" type="submit">Merge Selected</button>
              </form>

              <form method="post" style="border:1px dashed var(--border);border-radius:var(--radius-md);padding:var(--space-sm);">
                <input type="hidden" name="action" value="ignore_cluster">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="normalized_email" value="<?php echo htmlspecialchars($normalizedEmail, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                  <label for="reason_<?php echo htmlspecialchars(md5($normalizedEmail), ENT_QUOTES, 'UTF-8'); ?>">Ignore reason (optional)</label>
                  <input id="reason_<?php echo htmlspecialchars(md5($normalizedEmail), ENT_QUOTES, 'UTF-8'); ?>" name="reason" type="text" placeholder="Why this cluster is ignored">
                </div>
                <button class="button button--outline" type="submit">Ignore Cluster</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="section">
    <h2>Ignored Clusters</h2>
    <?php if ($ignoredClusters === []): ?>
      <p>No ignored clusters.</p>
    <?php else: ?>
      <div class="admin-list">
        <?php foreach ($ignoredClusters as $cluster): ?>
          <?php $normalizedEmail = (string)$cluster['normalized_email']; ?>
          <div class="admin-list-item">
            <div class="admin-list-item__info">
              <strong><?php echo htmlspecialchars($normalizedEmail, ENT_QUOTES, 'UTF-8'); ?></strong>
              <span class="admin-list-item__meta">
                Ignored by <?php echo htmlspecialchars((string)$cluster['ignored_by'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if (trim((string)$cluster['ignored_at']) !== ''): ?>
                  on <?php echo htmlspecialchars((string)$cluster['ignored_at'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
              </span>
              <?php if (trim((string)$cluster['ignored_reason']) !== ''): ?>
                <span class="admin-list-item__meta">Reason: <?php echo htmlspecialchars((string)$cluster['ignored_reason'], ENT_QUOTES, 'UTF-8'); ?></span>
              <?php endif; ?>
            </div>
            <div class="admin-list-item__actions">
              <form method="post">
                <input type="hidden" name="action" value="unignore_cluster">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="normalized_email" value="<?php echo htmlspecialchars($normalizedEmail, ENT_QUOTES, 'UTF-8'); ?>">
                <button class="button button--outline" type="submit">Reopen Cluster</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
