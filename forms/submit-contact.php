<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$contactPath = '/contact';

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
$honeypot = trim((string)($_POST['fax'] ?? ''));
if ($honeypot !== '') {
    $_SESSION['contact_success'] = 'Message sent. Thank you for reaching out.';
    $redirect($contactPath);
}

// CSRF
$token        = (string)($_POST['_token'] ?? '');
$sessionToken = (string)($_SESSION['contact_token'] ?? '');

// Collect input
$inquiry_type = trim((string)($_POST['inquiry_type'] ?? ''));
$name         = trim((string)($_POST['name'] ?? ''));
$email        = trim((string)($_POST['email'] ?? ''));
$subject      = trim((string)($_POST['subject'] ?? ''));
$message      = trim((string)($_POST['message'] ?? ''));

$oldInput = [
    'inquiry_type' => $inquiry_type,
    'name'         => $name,
    'email'        => $email,
    'subject'      => $subject,
    'message'      => $message,
];

$errors = [];
$strLen = static function (string $v): int {
    return function_exists('mb_strlen') ? mb_strlen($v) : strlen($v);
};

// Validate
if ($sessionToken === '' || $token === '' || !hash_equals($sessionToken, $token)) {
    $errors['form'] = 'Your session expired. Please refresh the page and try again.';
}

$validTypes = ['general', 'media', 'speaking', 'reader', 'arc'];
if (!in_array($inquiry_type, $validTypes, true)) {
    $errors['inquiry_type'] = 'Please select an inquiry type.';
}
if ($name === '' || $strLen($name) < 2 || $strLen($name) > 120) {
    $errors['name'] = 'Please enter your name (2-120 characters).';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please provide a valid email address.';
}
if ($subject === '' || $strLen($subject) > 200) {
    $errors['subject'] = 'Please provide a subject (up to 200 characters).';
}
if ($message === '' || $strLen($message) > 5000) {
    $errors['message'] = 'Please enter your message (up to 5000 characters).';
}

if ($errors !== []) {
    $_SESSION['contact_errors'] = $errors;
    $_SESSION['contact_old']    = $oldInput;
    $redirect($contactPath);
}

// Log submission
$storageDir = __DIR__ . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$submission = [
    'submitted_at' => gmdate('Y-m-d H:i:s'),
    'inquiry_type' => $inquiry_type,
    'name'         => $name,
    'email'        => strtolower($email),
    'subject'      => $subject,
    'message'      => $message,
    'ip'           => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
    'user_agent'   => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
];

$line = json_encode($submission, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($line !== false) {
    file_put_contents($storageDir . '/contact-submissions.ndjson', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Reset token
unset($_SESSION['contact_old'], $_SESSION['contact_errors']);
$_SESSION['contact_token'] = bin2hex(random_bytes(32));

$_SESSION['contact_success'] = 'Your message has been sent. Thank you for reaching out — we will get back to you as soon as possible.';
$redirect($contactPath);
