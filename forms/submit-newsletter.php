<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Redirect back to referring page
$referer = $_SERVER['HTTP_REFERER'] ?? '/';
$redirect_to = parse_url($referer, PHP_URL_PATH) ?: '/';

$redirect = static function (string $url): void {
    header('Location: ' . $url, true, 303);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// Honeypot
if (trim((string)($_POST['fax'] ?? '')) !== '') {
    $_SESSION['newsletter_success'] = 'You have been subscribed. Thank you!';
    $redirect($redirect_to);
}

$email    = trim((string)($_POST['email'] ?? ''));
$name     = trim((string)($_POST['name'] ?? ''));
$interest = (string)($_POST['interest'] ?? 'both');

// Validate
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['newsletter_error'] = 'Please provide a valid email address.';
    $redirect($redirect_to);
}

$valid_interests = ['fiction', 'non-fiction', 'both'];
if (!in_array($interest, $valid_interests, true)) {
    $interest = 'both';
}

// Insert into database
require_once dirname(__DIR__) . '/includes/db.php';
$pdo = get_db();

$stmt = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE email = ?');
$stmt->execute([strtolower($email)]);

if ($stmt->fetch()) {
    // Already subscribed — update interest
    $pdo->prepare('UPDATE newsletter_subscribers SET name = ?, interest = ? WHERE email = ?')
        ->execute([$name, $interest, strtolower($email)]);
} else {
    $pdo->prepare('INSERT INTO newsletter_subscribers (email, name, interest) VALUES (?, ?, ?)')
        ->execute([strtolower($email), $name, $interest]);

    // Queue welcome email
    $pdo->prepare('INSERT INTO email_queue (recipient_email, recipient_name, template_key, language) VALUES (?, ?, ?, ?)')
        ->execute([strtolower($email), $name, 'newsletter_welcome_1', 'English']);
}

// Log
$storageDir = __DIR__ . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}
$line = json_encode([
    'event'   => 'newsletter_signup',
    'email'   => strtolower($email),
    'name'    => $name,
    'interest' => $interest,
    'at'      => gmdate('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($line !== false) {
    file_put_contents($storageDir . '/newsletter-signups.ndjson', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$_SESSION['newsletter_success'] = 'You are subscribed! Welcome aboard.';
$redirect($redirect_to);
