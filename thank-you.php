<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$source = strtolower(trim((string)($_GET['source'] ?? 'general')));
$allowedSources = ['contact', 'accessibility', 'newsletter', 'general'];
if (!in_array($source, $allowedSources, true)) {
    $source = 'general';
}

$defaultsBySource = [
    'contact' => [
        'title' => 'Message Received',
        'message' => 'Thank you for reaching out. Your message is in the queue and a response will follow as soon as possible.',
    ],
    'accessibility' => [
        'title' => 'Accessibility Report Received',
        'message' => 'Thank you for reporting this accessibility issue. Your report helps improve the site for every reader.',
    ],
    'newsletter' => [
        'title' => 'Subscription Confirmed',
        'message' => 'Thank you for subscribing. You are now on the list for future updates, announcements, and new releases.',
    ],
    'general' => [
        'title' => 'Thank You',
        'message' => 'Your submission has been received successfully.',
    ],
];

$payload = $_SESSION['thank_you_payload'] ?? null;
unset($_SESSION['thank_you_payload']);

$title = $defaultsBySource[$source]['title'];
$message = $defaultsBySource[$source]['message'];
$reference = '';

if (is_array($payload)) {
    $payloadSource = strtolower(trim((string)($payload['source'] ?? '')));
    if ($payloadSource === '' || $payloadSource === $source) {
        $payloadTitle = trim((string)($payload['title'] ?? ''));
        $payloadMessage = trim((string)($payload['message'] ?? ''));
        $payloadReference = trim((string)($payload['reference'] ?? ''));

        if ($payloadTitle !== '') {
            $title = $payloadTitle;
        }
        if ($payloadMessage !== '') {
            $message = $payloadMessage;
        }
        if ($payloadReference !== '') {
            $reference = $payloadReference;
        }
    }
}

$page_title = 'Thank You | AuthorJuanJose.io';
$page_description = 'Thank you for connecting with AuthorJuanJose.io.';
require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="container page-shell">
  <div class="container--narrow" style="margin:0 auto">
    <h1>Thank You</h1>
    <div class="alert alert--success" role="status">
      <strong><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></strong><br>
      <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
      <?php if ($reference !== ''): ?>
        <br><br>
        <strong>Reference:</strong> <?php echo htmlspecialchars($reference, ENT_QUOTES, 'UTF-8'); ?>
      <?php endif; ?>
    </div>

    <section class="panel mt-lg">
      <h2>Keep Exploring</h2>
      <p>While you are here, take a look at what is happening across the site.</p>
      <div class="card-grid">
        <a class="card" href="/start-here" style="text-decoration:none;color:var(--ink)">
          <h3>Start Here</h3>
          <p>Find the best path based on what you want to read first.</p>
        </a>
        <a class="card" href="/fiction" style="text-decoration:none;color:var(--ink)">
          <h3>Fiction</h3>
          <p>Explore steampunk and science-fiction titles and worlds.</p>
        </a>
        <a class="card" href="/journal" style="text-decoration:none;color:var(--ink)">
          <h3>Journal</h3>
          <p>Read new updates, essays, and behind-the-book notes.</p>
        </a>
        <a class="card" href="/arc-reader-club" style="text-decoration:none;color:var(--ink)">
          <h3>ARC Reader Club</h3>
          <p>Join the reader community and get early-access opportunities.</p>
        </a>
      </div>
    </section>

    <p class="mt-lg">
      <a class="button" href="/">Return Home</a>
      <a class="button button--outline" href="/library" style="margin-left:.5rem">Browse Library</a>
    </p>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
