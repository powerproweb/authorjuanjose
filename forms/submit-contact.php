<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once dirname(__DIR__) . '/includes/contact-inbox-db.php';
require_once dirname(__DIR__) . '/includes/rate-limit.php';

$contactPath = '/contact';
$thankYouPath = '/thank-you?source=contact';

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
    $_SESSION['thank_you_payload'] = [
        'source' => 'contact',
        'title' => 'Message Received',
        'message' => 'Thank you for reaching out. Your message was received successfully.',
    ];
    $redirect($thankYouPath);
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
$humanSliderValue = (int)($_POST['human_slider_value'] ?? 0);
$postedHumanTargetValue = (int)($_POST['human_target_value'] ?? 0);
$postedHumanElapsedMs = (int)($_POST['human_elapsed_ms'] ?? 0);
$humanTargetValue = (int)($_SESSION['contact_human_target'] ?? 0);
$formStartedAt = (float)($_SESSION['contact_form_started_at'] ?? 0);
$humanElapsedMs = $formStartedAt > 0 ? (int)round((microtime(true) - $formStartedAt) * 1000) : 0;

$oldInput = [
    'inquiry_type' => $inquiry_type,
    'name'         => $name,
    'email'        => $email,
    'subject'      => $subject,
    'message'      => $message,
    'human_slider_value' => (string)$humanSliderValue,
    'human_target_value' => (string)$postedHumanTargetValue,
    'human_elapsed_ms' => (string)$postedHumanElapsedMs,
];

$errors = [];
$strLen = static function (string $v): int {
    return function_exists('mb_strlen') ? mb_strlen($v) : strlen($v);
};

// Validate
if ($sessionToken === '' || $token === '' || !hash_equals($sessionToken, $token)) {
    $errors['form'] = 'Your session expired. Please refresh the page and try again.';
}
$contactRateLimit = ajj_rate_limit_check('contact_form', ajj_rate_limit_client_ip(), 6, 300);
if (!$contactRateLimit['allowed']) {
    $errors['form'] = 'Too many messages were sent from your network. Please wait a few minutes and try again.';
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
if (
    $humanTargetValue < 60
    || $humanTargetValue > 92
    || $postedHumanTargetValue < 60
    || $postedHumanTargetValue > 92
    || $postedHumanTargetValue !== $humanTargetValue
) {
    $errors['form'] = 'Security check expired. Please refresh the page and try again.';
}
if ($humanSliderValue < $postedHumanTargetValue) {
    $errors['human_slider'] = 'Slide to 100% before submitting. The anti-bot bouncer is strict.';
}
if ($humanElapsedMs < 1200) {
    $errors['human_slider'] = 'Please wait one second and submit again. We do not accept speedrun bots.';
}
if ($postedHumanElapsedMs > 0 && $postedHumanElapsedMs < 1200) {
    $errors['human_slider'] = 'Please wait one second and submit again. We do not accept speedrun bots.';
}

if ($errors !== []) {
    $_SESSION['contact_errors'] = $errors;
    $_SESSION['contact_old']    = $oldInput;
    $redirect($contactPath);
}
// Store submission (DB first, file fallback)
$ticketRef = ajj_contact_generate_ticket_ref();
$submittedAt = gmdate('Y-m-d H:i:s');
$normalizedEmail = strtolower($email);
$ipAddress = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$userAgent = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
$savedToDb = false;

try {
    $pdo = get_contact_inbox_db();
    $stmt = $pdo->prepare(
        'INSERT INTO contact_submissions (
            ticket_ref, inquiry_type, full_name, email, subject, message,
            consent_contact, ip_address, user_agent, status, submitted_at, updated_at
        ) VALUES (
            :ticket_ref, :inquiry_type, :full_name, :email, :subject, :message,
            :consent_contact, :ip_address, :user_agent, :status, :submitted_at, :updated_at
        )'
    );
    $stmt->execute([
        ':ticket_ref' => $ticketRef,
        ':inquiry_type' => $inquiry_type,
        ':full_name' => $name,
        ':email' => $normalizedEmail,
        ':subject' => $subject,
        ':message' => $message,
        ':consent_contact' => 1,
        ':ip_address' => $ipAddress,
        ':user_agent' => $userAgent,
        ':status' => 'new',
        ':submitted_at' => $submittedAt,
        ':updated_at' => $submittedAt,
    ]);

    ajj_contact_log_event(
        $pdo,
        $ticketRef,
        'system',
        null,
        'new',
        'Submission received from public contact form.',
        'public-contact-form'
    );
    $savedToDb = true;
} catch (Throwable $e) {
    error_log('Contact inbox DB write failed for ' . $normalizedEmail . ': ' . $e->getMessage());
}
$savedToFile = false;
if (!$savedToDb) {
    $storageDir = __DIR__ . '/storage';
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0755, true);
    }

    $submission = [
        'ticket_ref'   => $ticketRef,
        'submitted_at' => $submittedAt,
        'inquiry_type' => $inquiry_type,
        'name'         => $name,
        'email'        => $normalizedEmail,
        'subject'      => $subject,
        'message'      => $message,
        'ip'           => $ipAddress,
        'user_agent'   => $userAgent,
        'db_saved'     => false,
    ];

    $line = json_encode($submission, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($line !== false) {
        $savedToFile = file_put_contents($storageDir . '/contact-submissions.ndjson', $line . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
    }
    if ($savedToFile) {
        error_log('Contact inbox fallback file write used for ticket ' . $ticketRef . '.');
    }
}

if (!$savedToDb && !$savedToFile) {
    $_SESSION['contact_errors'] = ['form' => 'We could not save your message right now. Please try again in a moment.'];
    $_SESSION['contact_old'] = $oldInput;
    $redirect($contactPath);
}

// Reset token
unset($_SESSION['contact_old'], $_SESSION['contact_errors']);
unset($_SESSION['contact_human_target'], $_SESSION['contact_form_started_at']);
$_SESSION['contact_token'] = bin2hex(random_bytes(32));
$_SESSION['thank_you_payload'] = [
    'source' => 'contact',
    'title' => 'Message Received',
    'message' => 'Your message has been sent. We will get back to you as soon as possible.',
    'reference' => $ticketRef,
];
$redirect($thankYouPath);
