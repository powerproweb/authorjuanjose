<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
$adminActor = ajj_require_admin_auth('AuthorJuanJose Contact Inbox');
if (!defined('SITE_AUTH_GATE_ENABLED')) {
    define('SITE_AUTH_GATE_ENABLED', false);
}
require_once dirname(__DIR__) . '/includes/contact-inbox-db.php';

$pdo = get_contact_inbox_db();

$statusOptions = ['new', 'reviewed', 'resolved', 'spam'];
$viewOptions = ['active', 'archived'];

$normalizeView = static function (string $value) use ($viewOptions): string {
    return in_array($value, $viewOptions, true) ? $value : 'active';
};
$normalizeStatus = static function (string $value) use ($statusOptions): string {
    if ($value === 'all') {
        return 'all';
    }
    return in_array($value, $statusOptions, true) ? $value : 'all';
};
$buildAdminUrl = static function (array $params): string {
    $filtered = [];
    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $filtered[$key] = $value;
    }
    $query = http_build_query($filtered);
    return '/admin/form-submissions' . ($query !== '' ? '?' . $query : '');
};

if (!isset($_SESSION['admin_contact_inbox_token']) || !is_string($_SESSION['admin_contact_inbox_token']) || $_SESSION['admin_contact_inbox_token'] === '') {
    $_SESSION['admin_contact_inbox_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_contact_inbox_token'];

$view = $normalizeView((string)($_GET['view'] ?? 'active'));
$statusFilter = $normalizeStatus((string)($_GET['status'] ?? 'all'));
$selectedTicket = trim((string)($_GET['ticket'] ?? ''));
$selectedArchiveId = (int)($_GET['archive_id'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $contextView = $normalizeView((string)($_POST['redirect_view'] ?? $view));
    $contextStatus = $normalizeStatus((string)($_POST['redirect_status'] ?? $statusFilter));
    $contextTicket = trim((string)($_POST['redirect_ticket'] ?? $selectedTicket));
    $contextArchiveId = (int)($_POST['redirect_archive_id'] ?? $selectedArchiveId);

    $redirectParams = ['view' => $contextView];
    if ($contextView === 'active') {
        $redirectParams['status'] = $contextStatus;
        if ($contextTicket !== '') {
            $redirectParams['ticket'] = $contextTicket;
        }
    } else {
        if ($contextArchiveId > 0) {
            $redirectParams['archive_id'] = (string)$contextArchiveId;
        }
    }

    $postedToken = (string)($_POST['_token'] ?? '');
    if ($postedToken === '' || !hash_equals($csrfToken, $postedToken)) {
        $_SESSION['contact_admin_flash_error'] = 'Invalid request token. Please try again.';
        header('Location: ' . $buildAdminUrl($redirectParams), true, 303);
        exit;
    }

    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'update_status') {
            $ticketRef = trim((string)($_POST['ticket_ref'] ?? ''));
            $nextStatus = $normalizeStatus((string)($_POST['status'] ?? ''));
            if ($ticketRef === '' || $nextStatus === 'all') {
                throw new RuntimeException('A valid ticket and status are required.');
            }

            $stmt = $pdo->prepare('SELECT status FROM contact_submissions WHERE ticket_ref = :ticket_ref');
            $stmt->execute([':ticket_ref' => $ticketRef]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new RuntimeException('The selected submission was not found.');
            }

            $previousStatus = (string)$row['status'];
            if ($previousStatus !== $nextStatus) {
                $now = gmdate('Y-m-d H:i:s');
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE contact_submissions SET status = :status, updated_at = :updated_at WHERE ticket_ref = :ticket_ref')
                    ->execute([
                        ':status' => $nextStatus,
                        ':updated_at' => $now,
                        ':ticket_ref' => $ticketRef,
                    ]);
                $statusNote = trim((string)($_POST['status_note'] ?? ''));
                if ($statusNote === '') {
                    $statusNote = 'Status updated in admin inbox.';
                }
                ajj_contact_log_event($pdo, $ticketRef, 'status_change', $previousStatus, $nextStatus, $statusNote, $adminActor);
                $pdo->commit();
            }

            $_SESSION['contact_admin_flash_success'] = 'Status updated for ' . $ticketRef . '.';
            $redirectParams['view'] = 'active';
            $redirectParams['status'] = $contextStatus;
            $redirectParams['ticket'] = $ticketRef;
        } elseif ($action === 'add_note') {
            $ticketRef = trim((string)($_POST['ticket_ref'] ?? ''));
            $note = trim((string)($_POST['note'] ?? ''));
            $strlen = function_exists('mb_strlen') ? mb_strlen($note) : strlen($note);
            if ($ticketRef === '' || $note === '') {
                throw new RuntimeException('A note is required.');
            }
            if ($strlen > 2000) {
                throw new RuntimeException('Notes must be 2000 characters or fewer.');
            }

            $stmt = $pdo->prepare('SELECT 1 FROM contact_submissions WHERE ticket_ref = :ticket_ref');
            $stmt->execute([':ticket_ref' => $ticketRef]);
            if (!$stmt->fetch()) {
                throw new RuntimeException('The selected submission was not found.');
            }

            $pdo->beginTransaction();
            ajj_contact_log_event($pdo, $ticketRef, 'note', null, null, $note, $adminActor);
            $pdo->prepare('UPDATE contact_submissions SET updated_at = :updated_at WHERE ticket_ref = :ticket_ref')
                ->execute([
                    ':updated_at' => gmdate('Y-m-d H:i:s'),
                    ':ticket_ref' => $ticketRef,
                ]);
            $pdo->commit();

            $_SESSION['contact_admin_flash_success'] = 'Note saved for ' . $ticketRef . '.';
            $redirectParams['view'] = 'active';
            $redirectParams['status'] = $contextStatus;
            $redirectParams['ticket'] = $ticketRef;
        } elseif ($action === 'archive') {
            $ticketRef = trim((string)($_POST['ticket_ref'] ?? ''));
            $confirmPhrase = strtoupper(trim((string)($_POST['archive_confirm'] ?? '')));
            if ($ticketRef === '') {
                throw new RuntimeException('A ticket reference is required.');
            }
            if ($confirmPhrase !== 'ARCHIVE') {
                throw new RuntimeException('Type ARCHIVE to confirm archival.');
            }

            $submissionStmt = $pdo->prepare('SELECT * FROM contact_submissions WHERE ticket_ref = :ticket_ref');
            $submissionStmt->execute([':ticket_ref' => $ticketRef]);
            $submission = $submissionStmt->fetch();
            if (!$submission) {
                throw new RuntimeException('The selected submission was not found.');
            }

            $eventsStmt = $pdo->prepare('SELECT * FROM contact_submission_events WHERE ticket_ref = :ticket_ref ORDER BY created_at ASC, id ASC');
            $eventsStmt->execute([':ticket_ref' => $ticketRef]);
            $events = $eventsStmt->fetchAll();

            $pdo->beginTransaction();
            $archiveStmt = $pdo->prepare(
                'INSERT INTO contact_submission_archive (submission_id, ticket_ref, payload_json, events_json, archived_by)
                 VALUES (:submission_id, :ticket_ref, :payload_json, :events_json, :archived_by)'
            );
            $archiveStmt->execute([
                ':submission_id' => (int)$submission['id'],
                ':ticket_ref' => $ticketRef,
                ':payload_json' => json_encode($submission, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':events_json' => json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':archived_by' => $adminActor,
            ]);
            $pdo->prepare('DELETE FROM contact_submissions WHERE ticket_ref = :ticket_ref')->execute([':ticket_ref' => $ticketRef]);
            $pdo->commit();

            $_SESSION['contact_admin_flash_success'] = 'Submission ' . $ticketRef . ' archived.';
            $redirectParams = ['view' => 'archived'];
        } elseif ($action === 'restore') {
            $archiveId = (int)($_POST['archive_id'] ?? 0);
            if ($archiveId <= 0) {
                throw new RuntimeException('An archive record is required.');
            }

            $archiveStmt = $pdo->prepare('SELECT * FROM contact_submission_archive WHERE id = :id');
            $archiveStmt->execute([':id' => $archiveId]);
            $archive = $archiveStmt->fetch();
            if (!$archive) {
                throw new RuntimeException('Archive record not found.');
            }

            $payload = json_decode((string)$archive['payload_json'], true);
            $events = json_decode((string)$archive['events_json'], true);
            if (!is_array($payload) || !isset($payload['ticket_ref'])) {
                throw new RuntimeException('Archive payload is invalid.');
            }

            $ticketRef = trim((string)$payload['ticket_ref']);
            if ($ticketRef === '') {
                throw new RuntimeException('Archive payload is missing a ticket reference.');
            }

            $existsStmt = $pdo->prepare('SELECT 1 FROM contact_submissions WHERE ticket_ref = :ticket_ref');
            $existsStmt->execute([':ticket_ref' => $ticketRef]);
            if ($existsStmt->fetch()) {
                throw new RuntimeException('A live submission with this ticket reference already exists.');
            }

            $restoredStatus = (string)($payload['status'] ?? 'new');
            if (!in_array($restoredStatus, $statusOptions, true)) {
                $restoredStatus = 'new';
            }

            $pdo->beginTransaction();
            $pdo->prepare(
                'INSERT INTO contact_submissions (
                    ticket_ref, inquiry_type, full_name, email, subject, message, consent_contact,
                    ip_address, user_agent, status, submitted_at, updated_at
                ) VALUES (
                    :ticket_ref, :inquiry_type, :full_name, :email, :subject, :message, :consent_contact,
                    :ip_address, :user_agent, :status, :submitted_at, :updated_at
                )'
            )->execute([
                ':ticket_ref' => $ticketRef,
                ':inquiry_type' => (string)($payload['inquiry_type'] ?? 'general'),
                ':full_name' => (string)($payload['full_name'] ?? ''),
                ':email' => (string)($payload['email'] ?? ''),
                ':subject' => (string)($payload['subject'] ?? ''),
                ':message' => (string)($payload['message'] ?? ''),
                ':consent_contact' => (int)($payload['consent_contact'] ?? 1),
                ':ip_address' => (string)($payload['ip_address'] ?? ''),
                ':user_agent' => (string)($payload['user_agent'] ?? ''),
                ':status' => $restoredStatus,
                ':submitted_at' => (string)($payload['submitted_at'] ?? gmdate('Y-m-d H:i:s')),
                ':updated_at' => (string)($payload['updated_at'] ?? gmdate('Y-m-d H:i:s')),
            ]);

            if (is_array($events)) {
                foreach ($events as $event) {
                    if (!is_array($event)) {
                        continue;
                    }

                    $eventType = (string)($event['event_type'] ?? 'system');
                    if (!in_array($eventType, ['status_change', 'note', 'system'], true)) {
                        $eventType = 'system';
                    }

                    $pdo->prepare(
                        'INSERT INTO contact_submission_events (
                            ticket_ref, event_type, from_status, to_status, note, actor, created_at
                        ) VALUES (
                            :ticket_ref, :event_type, :from_status, :to_status, :note, :actor, :created_at
                        )'
                    )->execute([
                        ':ticket_ref' => $ticketRef,
                        ':event_type' => $eventType,
                        ':from_status' => isset($event['from_status']) ? (string)$event['from_status'] : null,
                        ':to_status' => isset($event['to_status']) ? (string)$event['to_status'] : null,
                        ':note' => (string)($event['note'] ?? ''),
                        ':actor' => (string)($event['actor'] ?? 'system'),
                        ':created_at' => (string)($event['created_at'] ?? gmdate('Y-m-d H:i:s')),
                    ]);
                }
            }

            ajj_contact_log_event($pdo, $ticketRef, 'system', null, null, 'Submission restored from archive.', $adminActor);
            $pdo->prepare('DELETE FROM contact_submission_archive WHERE id = :id')->execute([':id' => $archiveId]);
            $pdo->commit();

            $_SESSION['contact_admin_flash_success'] = 'Submission ' . $ticketRef . ' restored.';
            $redirectParams = [
                'view' => 'active',
                'status' => 'all',
                'ticket' => $ticketRef,
            ];
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['contact_admin_flash_error'] = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Action failed. Please try again.';
        if (!$e instanceof RuntimeException) {
            error_log('Admin contact inbox action failed: ' . $e->getMessage());
        }
    }

    header('Location: ' . $buildAdminUrl($redirectParams), true, 303);
    exit;
}

$flashSuccess = (string)($_SESSION['contact_admin_flash_success'] ?? '');
$flashError = (string)($_SESSION['contact_admin_flash_error'] ?? '');
unset($_SESSION['contact_admin_flash_success'], $_SESSION['contact_admin_flash_error']);

$statusCounts = ['new' => 0, 'reviewed' => 0, 'resolved' => 0, 'spam' => 0];
$statusRows = $pdo->query('SELECT status, COUNT(*) AS count FROM contact_submissions GROUP BY status')->fetchAll();
foreach ($statusRows as $row) {
    $status = (string)$row['status'];
    if (isset($statusCounts[$status])) {
        $statusCounts[$status] = (int)$row['count'];
    }
}
$activeTotal = array_sum($statusCounts);
$archivedTotal = (int)$pdo->query('SELECT COUNT(*) FROM contact_submission_archive')->fetchColumn();

$activeSubmissions = [];
$archivedSubmissions = [];
$selectedSubmission = null;
$selectedEvents = [];
$selectedArchive = null;
$selectedArchivePayload = null;
$selectedArchiveEvents = [];

if ($view === 'active') {
    $params = [];
    $sql = 'SELECT ticket_ref, inquiry_type, full_name, email, subject, status, submitted_at, updated_at
            FROM contact_submissions';
    if ($statusFilter !== 'all') {
        $sql .= ' WHERE status = :status';
        $params[':status'] = $statusFilter;
    }
    $sql .= ' ORDER BY submitted_at DESC LIMIT 200';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $activeSubmissions = $stmt->fetchAll();

    if ($selectedTicket === '' && $activeSubmissions !== []) {
        $selectedTicket = (string)$activeSubmissions[0]['ticket_ref'];
    }
    if ($selectedTicket !== '') {
        $submissionStmt = $pdo->prepare('SELECT * FROM contact_submissions WHERE ticket_ref = :ticket_ref');
        $submissionStmt->execute([':ticket_ref' => $selectedTicket]);
        $selectedSubmission = $submissionStmt->fetch() ?: null;

        if ($selectedSubmission) {
            $eventsStmt = $pdo->prepare(
                'SELECT * FROM contact_submission_events WHERE ticket_ref = :ticket_ref ORDER BY created_at DESC, id DESC'
            );
            $eventsStmt->execute([':ticket_ref' => $selectedTicket]);
            $selectedEvents = $eventsStmt->fetchAll();
        }
    }
} else {
    $stmt = $pdo->query(
        'SELECT id, ticket_ref, archived_by, archived_at
         FROM contact_submission_archive
         ORDER BY archived_at DESC, id DESC
         LIMIT 200'
    );
    $archivedSubmissions = $stmt->fetchAll();

    if ($selectedArchiveId <= 0 && $archivedSubmissions !== []) {
        $selectedArchiveId = (int)$archivedSubmissions[0]['id'];
    }
    if ($selectedArchiveId > 0) {
        $archiveStmt = $pdo->prepare('SELECT * FROM contact_submission_archive WHERE id = :id');
        $archiveStmt->execute([':id' => $selectedArchiveId]);
        $selectedArchive = $archiveStmt->fetch() ?: null;
        if ($selectedArchive) {
            $decodedPayload = json_decode((string)$selectedArchive['payload_json'], true);
            $decodedEvents = json_decode((string)$selectedArchive['events_json'], true);
            $selectedArchivePayload = is_array($decodedPayload) ? $decodedPayload : null;
            $selectedArchiveEvents = is_array($decodedEvents) ? $decodedEvents : [];
        }
    }
}

$h = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$page_title = 'Contact Inbox | Admin';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">
  <h1>Contact Inbox</h1>
  <p><a href="/admin">&larr; Admin Dashboard</a></p>

  <?php if ($flashSuccess !== ''): ?>
    <div class="alert alert--success" role="status"><?php echo $h($flashSuccess); ?></div>
  <?php endif; ?>
  <?php if ($flashError !== ''): ?>
    <div class="alert alert--error" role="alert"><?php echo $h($flashError); ?></div>
  <?php endif; ?>

  <div class="admin-filters">
    <a class="button <?php echo $view === 'active' ? '' : 'button--outline'; ?>" href="/admin/form-submissions?view=active&status=<?php echo $h($statusFilter); ?>">
      Active (<?php echo $activeTotal; ?>)
    </a>
    <a class="button <?php echo $view === 'archived' ? '' : 'button--outline'; ?>" href="/admin/form-submissions?view=archived">
      Archived (<?php echo $archivedTotal; ?>)
    </a>
  </div>

  <?php if ($view === 'active'): ?>
    <div class="admin-filters">
      <a class="button <?php echo $statusFilter === 'all' ? '' : 'button--outline'; ?>" href="/admin/form-submissions?view=active&status=all">All (<?php echo $activeTotal; ?>)</a>
      <a class="button <?php echo $statusFilter === 'new' ? '' : 'button--outline'; ?>" href="/admin/form-submissions?view=active&status=new">New (<?php echo $statusCounts['new']; ?>)</a>
      <a class="button <?php echo $statusFilter === 'reviewed' ? '' : 'button--outline'; ?>" href="/admin/form-submissions?view=active&status=reviewed">Reviewed (<?php echo $statusCounts['reviewed']; ?>)</a>
      <a class="button <?php echo $statusFilter === 'resolved' ? '' : 'button--outline'; ?>" href="/admin/form-submissions?view=active&status=resolved">Resolved (<?php echo $statusCounts['resolved']; ?>)</a>
      <a class="button <?php echo $statusFilter === 'spam' ? '' : 'button--outline'; ?>" href="/admin/form-submissions?view=active&status=spam">Spam (<?php echo $statusCounts['spam']; ?>)</a>
    </div>
  <?php endif; ?>

  <div class="card-grid">
    <section class="panel">
      <h2><?php echo $view === 'active' ? 'Active Submissions' : 'Archived Submissions'; ?></h2>
      <?php if ($view === 'active'): ?>
        <?php if ($activeSubmissions === []): ?>
          <p>No submissions match this filter.</p>
        <?php else: ?>
          <div class="admin-list">
            <?php foreach ($activeSubmissions as $submission): ?>
              <?php
              $ticketRef = (string)$submission['ticket_ref'];
              $isSelected = $selectedSubmission && (string)$selectedSubmission['ticket_ref'] === $ticketRef;
              ?>
              <div class="admin-list-item" style="<?php echo $isSelected ? 'border:1px solid var(--accent);' : ''; ?>">
                <div class="admin-list-item__info">
                  <strong>
                    <a href="/admin/form-submissions?view=active&status=<?php echo $h($statusFilter); ?>&ticket=<?php echo $h($ticketRef); ?>">
                      <?php echo $h($ticketRef); ?>
                    </a>
                  </strong>
                  <span class="admin-list-item__email"><?php echo $h($submission['email']); ?></span>
                  <span class="admin-list-item__meta">
                    <?php echo ucfirst($h($submission['inquiry_type'])); ?> &middot;
                    <?php echo $h($submission['full_name']); ?>
                  </span>
                  <span class="admin-list-item__meta">
                    <?php echo $h($submission['submitted_at']); ?> &middot;
                    Status: <?php echo ucfirst($h($submission['status'])); ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <?php if ($archivedSubmissions === []): ?>
          <p>No archived submissions yet.</p>
        <?php else: ?>
          <div class="admin-list">
            <?php foreach ($archivedSubmissions as $archiveRow): ?>
              <?php
              $archiveId = (int)$archiveRow['id'];
              $isSelected = $selectedArchive && (int)$selectedArchive['id'] === $archiveId;
              ?>
              <div class="admin-list-item" style="<?php echo $isSelected ? 'border:1px solid var(--accent);' : ''; ?>">
                <div class="admin-list-item__info">
                  <strong>
                    <a href="/admin/form-submissions?view=archived&archive_id=<?php echo $archiveId; ?>">
                      <?php echo $h($archiveRow['ticket_ref']); ?>
                    </a>
                  </strong>
                  <span class="admin-list-item__meta">
                    Archived at <?php echo $h($archiveRow['archived_at']); ?> by <?php echo $h($archiveRow['archived_by']); ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </section>

    <section class="panel">
      <?php if ($view === 'active'): ?>
        <h2>Submission Detail</h2>
        <?php if (!$selectedSubmission): ?>
          <p>Select a submission from the list to inspect and manage it.</p>
        <?php else: ?>
          <p><strong><?php echo $h($selectedSubmission['ticket_ref']); ?></strong></p>
          <p class="admin-list-item__meta">
            <?php echo ucfirst($h($selectedSubmission['inquiry_type'])); ?> &middot;
            <?php echo $h($selectedSubmission['submitted_at']); ?> &middot;
            Status: <?php echo ucfirst($h($selectedSubmission['status'])); ?>
          </p>
          <p><strong><?php echo $h($selectedSubmission['full_name']); ?></strong> (<?php echo $h($selectedSubmission['email']); ?>)</p>
          <p><strong>Subject:</strong> <?php echo $h($selectedSubmission['subject']); ?></p>
          <p><strong>Message:</strong></p>
          <p><?php echo nl2br($h($selectedSubmission['message'])); ?></p>

          <div class="divider-gear"></div>

          <form method="post" class="panel" style="margin-top:var(--space-md);">
            <h3>Update Status</h3>
            <input type="hidden" name="_token" value="<?php echo $h($csrfToken); ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="ticket_ref" value="<?php echo $h($selectedSubmission['ticket_ref']); ?>">
            <input type="hidden" name="redirect_view" value="active">
            <input type="hidden" name="redirect_status" value="<?php echo $h($statusFilter); ?>">
            <input type="hidden" name="redirect_ticket" value="<?php echo $h($selectedSubmission['ticket_ref']); ?>">
            <div class="form-group">
              <label for="status">Status</label>
              <select id="status" name="status">
                <?php foreach ($statusOptions as $status): ?>
                  <option value="<?php echo $h($status); ?>" <?php echo $selectedSubmission['status'] === $status ? 'selected' : ''; ?>>
                    <?php echo ucfirst($h($status)); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="status_note">Status note (optional)</label>
              <textarea id="status_note" name="status_note" placeholder="What changed?"></textarea>
            </div>
            <button class="button" type="submit">Save Status</button>
          </form>

          <form method="post" class="panel" style="margin-top:var(--space-md);">
            <h3>Add Note</h3>
            <input type="hidden" name="_token" value="<?php echo $h($csrfToken); ?>">
            <input type="hidden" name="action" value="add_note">
            <input type="hidden" name="ticket_ref" value="<?php echo $h($selectedSubmission['ticket_ref']); ?>">
            <input type="hidden" name="redirect_view" value="active">
            <input type="hidden" name="redirect_status" value="<?php echo $h($statusFilter); ?>">
            <input type="hidden" name="redirect_ticket" value="<?php echo $h($selectedSubmission['ticket_ref']); ?>">
            <div class="form-group">
              <label for="note">Internal note</label>
              <textarea id="note" name="note" required maxlength="2000" placeholder="Add operational context or follow-up notes."></textarea>
            </div>
            <button class="button" type="submit">Add Note</button>
          </form>

          <form method="post" class="panel" style="margin-top:var(--space-md); border-color:var(--danger);">
            <h3>Archive Submission</h3>
            <input type="hidden" name="_token" value="<?php echo $h($csrfToken); ?>">
            <input type="hidden" name="action" value="archive">
            <input type="hidden" name="ticket_ref" value="<?php echo $h($selectedSubmission['ticket_ref']); ?>">
            <input type="hidden" name="redirect_view" value="active">
            <input type="hidden" name="redirect_status" value="<?php echo $h($statusFilter); ?>">
            <input type="hidden" name="redirect_ticket" value="<?php echo $h($selectedSubmission['ticket_ref']); ?>">
            <div class="form-group">
              <label for="archive_confirm">Type ARCHIVE to confirm</label>
              <input id="archive_confirm" name="archive_confirm" type="text" required pattern="ARCHIVE" autocomplete="off">
            </div>
            <button class="button button--outline" type="submit">Archive</button>
          </form>

          <div class="divider-gear"></div>

          <h3>Timeline</h3>
          <?php if ($selectedEvents === []): ?>
            <p>No events recorded yet.</p>
          <?php else: ?>
            <div class="admin-list">
              <?php foreach ($selectedEvents as $event): ?>
                <div class="admin-list-item">
                  <div class="admin-list-item__info">
                    <strong><?php echo ucfirst($h($event['event_type'])); ?></strong>
                    <span class="admin-list-item__meta">
                      <?php echo $h($event['created_at']); ?> &middot; <?php echo $h($event['actor']); ?>
                    </span>
                    <?php if (!empty($event['from_status']) || !empty($event['to_status'])): ?>
                      <span class="admin-list-item__meta">
                        <?php echo $h((string)($event['from_status'] ?? '')); ?> &rarr; <?php echo $h((string)($event['to_status'] ?? '')); ?>
                      </span>
                    <?php endif; ?>
                    <?php if ((string)($event['note'] ?? '') !== ''): ?>
                      <p><?php echo nl2br($h($event['note'])); ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      <?php else: ?>
        <h2>Archived Detail</h2>
        <?php if (!$selectedArchive || !$selectedArchivePayload): ?>
          <p>Select an archived submission to inspect or restore it.</p>
        <?php else: ?>
          <p><strong><?php echo $h($selectedArchive['ticket_ref']); ?></strong></p>
          <p class="admin-list-item__meta">
            Archived at <?php echo $h($selectedArchive['archived_at']); ?> by <?php echo $h($selectedArchive['archived_by']); ?>
          </p>
          <p><strong><?php echo $h($selectedArchivePayload['full_name'] ?? 'Unknown'); ?></strong> (<?php echo $h($selectedArchivePayload['email'] ?? ''); ?>)</p>
          <p><strong>Subject:</strong> <?php echo $h($selectedArchivePayload['subject'] ?? ''); ?></p>
          <p><strong>Message:</strong></p>
          <p><?php echo nl2br($h($selectedArchivePayload['message'] ?? '')); ?></p>

          <form method="post" class="panel" style="margin-top:var(--space-md);">
            <h3>Restore Submission</h3>
            <input type="hidden" name="_token" value="<?php echo $h($csrfToken); ?>">
            <input type="hidden" name="action" value="restore">
            <input type="hidden" name="archive_id" value="<?php echo (int)$selectedArchive['id']; ?>">
            <input type="hidden" name="redirect_view" value="archived">
            <input type="hidden" name="redirect_archive_id" value="<?php echo (int)$selectedArchive['id']; ?>">
            <button class="button" type="submit">Restore to Active Inbox</button>
          </form>

          <div class="divider-gear"></div>

          <h3>Archived Timeline Snapshot</h3>
          <?php if ($selectedArchiveEvents === []): ?>
            <p>No archived events found.</p>
          <?php else: ?>
            <div class="admin-list">
              <?php foreach ($selectedArchiveEvents as $event): ?>
                <?php if (!is_array($event)) { continue; } ?>
                <div class="admin-list-item">
                  <div class="admin-list-item__info">
                    <strong><?php echo ucfirst($h($event['event_type'] ?? 'system')); ?></strong>
                    <span class="admin-list-item__meta">
                      <?php echo $h($event['created_at'] ?? ''); ?> &middot; <?php echo $h($event['actor'] ?? 'system'); ?>
                    </span>
                    <?php if (!empty($event['from_status']) || !empty($event['to_status'])): ?>
                      <span class="admin-list-item__meta">
                        <?php echo $h($event['from_status'] ?? ''); ?> &rarr; <?php echo $h($event['to_status'] ?? ''); ?>
                      </span>
                    <?php endif; ?>
                    <?php if ((string)($event['note'] ?? '') !== ''): ?>
                      <p><?php echo nl2br($h($event['note'])); ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
