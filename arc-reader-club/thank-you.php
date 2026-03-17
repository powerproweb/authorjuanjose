<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$page_title = 'Application Received | ARC Reader Club';
$show_arc_sub_navigation = true;
require_once dirname(__DIR__) . '/includes/header.php';

$status = strtolower(trim((string)($_GET['status'] ?? 'success')));

$messageTitle = 'Application Received';
$messageBody = 'Thank you for applying to the ARC Reader Club. You are now on our radar for upcoming ARC opportunities.';
$messageClass = 'alert--success';

if ($status === 'queued') {
    $messageTitle = 'Application Received and Queued';
    $messageBody = 'Your submission was saved successfully. We are finalizing processing in the background and you will still be considered for upcoming ARC opportunities.';
    $messageClass = 'alert--info';
}

// Consume any remaining flash messages so they don't persist
unset($_SESSION['arc_form_success'], $_SESSION['arc_form_notice'], $_SESSION['arc_form_errors'], $_SESSION['arc_form_old']);
?>

<main class="container page-shell">
  <div class="container--narrow" style="margin:0 auto">
    <h1>Thank You</h1>
    <div class="alert <?php echo htmlspecialchars($messageClass, ENT_QUOTES, 'UTF-8'); ?>" role="status">
      <strong><?php echo htmlspecialchars($messageTitle, ENT_QUOTES, 'UTF-8'); ?></strong><br>
      <?php echo htmlspecialchars($messageBody, ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <section class="panel mt-lg">
      <h2>What Happens Next</h2>
      <ol class="steps">
        <li>
          <div>
            <strong>Review and Segmentation</strong>
            <p class="mb-0">Your application details are reviewed and placed into the appropriate language segment.</p>
          </div>
        </li>
        <li>
          <div>
            <strong>ARC Updates</strong>
            <p class="mb-0">You'll receive updates when ARC opportunities become available.</p>
          </div>
        </li>
        <li>
          <div>
            <strong>First Mission Invitation</strong>
            <p class="mb-0">When a book campaign opens, qualified members receive mission details and next steps by email.</p>
          </div>
        </li>
      </ol>
    </section>

    <p class="mt-lg">
      <a class="button" href="/arc-reader-club">Back to ARC Reader Club</a>
      <a class="button button--outline" href="/" style="margin-left:.5rem">Return Home</a>
    </p>
  </div>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
