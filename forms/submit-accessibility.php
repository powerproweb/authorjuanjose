<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once dirname(__DIR__) . '/includes/contact-inbox-db.php';
require_once dirname(__DIR__) . '/includes/rate-limit.php';

$accessibilityPath = '/accessibility';
$thankYouPath = '/thank-you?source=accessibility';

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

$honeypot = trim((string)($_POST['company'] ?? ''));
if ($honeypot !== '') {
    $_SESSION['thank_you_payload'] = [
        'source' => 'accessibility',
        'title' => 'Accessibility Report Received',
        'message' => 'Thank you for the accessibility report. Your submission was received.',
    ];
    $redirect($thankYouPath);
}

$token = (string)($_POST['_token'] ?? '');
$sessionToken = (string)($_SESSION['accessibility_token'] ?? '');

$fullName = trim((string)($_POST['full_name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$affectedUrl = trim((string)($_POST['affected_url'] ?? ''));
$issueType = trim((string)($_POST['issue_type'] ?? ''));
$issueSeverity = trim((string)($_POST['issue_severity'] ?? ''));
$assistiveTechnology = trim((string)($_POST['assistive_technology'] ?? ''));
$issueDetails = trim((string)($_POST['issue_details'] ?? ''));
$consentContact = (string)($_POST['consent_contact'] ?? '') === '1';

$oldInput = [
    'full_name' => $fullName,
    'email' => $email,
    'affected_url' => $affectedUrl,
    'issue_type' => $issueType,
    'issue_severity' => $issueSeverity,
    'assistive_technology' => $assistiveTechnology,
    'issue_details' => $issueDetails,
    'consent_contact' => $consentContact ? '1' : '',
];

$errors = [];
$strLen = static function (string $value): int {
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
};

$issueTypes = [
    'keyboard-navigation' => 'Keyboard navigation',
    'screen-reader' => 'Screen reader support',
    'contrast-visual' => 'Contrast or visual clarity',
    'form-control' => 'Form label or control behavior',
    'media-caption' => 'Media captions or transcripts',
    'motion-animation' => 'Motion or animation sensitivity',
    'other' => 'Other accessibility barrier',
];
$issueSeverities = [
    'low' => 'Low impact',
    'medium' => 'Medium impact',
    'high' => 'High impact',
    'critical' => 'Critical blocker',
];

if ($sessionToken === '' || $token === '' || !hash_equals($sessionToken, $token)) {
    $errors['form'] = 'Your session expired. Please refresh and submit again.';
}

$accessibilityRateLimit = ajj_rate_limit_check('accessibility_form', ajj_rate_limit_client_ip(), 4, 600);
if (!$accessibilityRateLimit['allowed']) {
    $errors['form'] = 'Too many reports were sent from your network. Please wait a few minutes and try again.';
}

if ($fullName === '' || $strLen($fullName) < 2 || $strLen($fullName) > 120) {
    $errors['full_name'] = 'Please enter your name (2-120 characters).';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please provide a valid email address.';
}

$isRelativePath = str_starts_with($affectedUrl, '/')
    && !str_contains($affectedUrl, ' ')
    && !str_contains($affectedUrl, "\t")
    && !str_contains($affectedUrl, "\n");
$parsedScheme = strtolower((string)parse_url($affectedUrl, PHP_URL_SCHEME));
$isAbsoluteUrl = filter_var($affectedUrl, FILTER_VALIDATE_URL) !== false
    && in_array($parsedScheme, ['http', 'https'], true);
if ($affectedUrl === '' || $strLen($affectedUrl) > 255 || (!$isRelativePath && !$isAbsoluteUrl)) {
    $errors['affected_url'] = 'Provide a valid URL (https://...) or a site path (/page).';
}

if (!array_key_exists($issueType, $issueTypes)) {
    $errors['issue_type'] = 'Please select an issue type.';
}
if (!array_key_exists($issueSeverity, $issueSeverities)) {
    $errors['issue_severity'] = 'Please select an impact level.';
}
if ($assistiveTechnology !== '' && $strLen($assistiveTechnology) > 180) {
    $errors['assistive_technology'] = 'Assistive technology context must be 180 characters or fewer.';
}
if ($issueDetails === '' || $strLen($issueDetails) < 20 || $strLen($issueDetails) > 5000) {
    $errors['issue_details'] = 'Please describe the issue in 20-5000 characters.';
}
if (!$consentContact) {
    $errors['consent_contact'] = 'You must allow follow-up contact so the team can resolve the issue.';
}

if ($errors !== []) {
    $_SESSION['accessibility_errors'] = $errors;
    $_SESSION['accessibility_old'] = $oldInput;
    $redirect($accessibilityPath);
}

$issueTypeLabel = $issueTypes[$issueType];
$issueSeverityLabel = $issueSeverities[$issueSeverity];
$normalizedEmail = strtolower($email);

$subject = 'Accessibility Report: ' . $issueTypeLabel . ' [' . $issueSeverityLabel . ']';
$message = implode(PHP_EOL, [
    'Accessibility report submitted from the public Accessibility page.',
    '',
    'Affected URL or path: ' . $affectedUrl,
    'Issue type: ' . $issueTypeLabel,
    'Impact level: ' . $issueSeverityLabel,
    'Reporter: ' . $fullName . ' (' . $normalizedEmail . ')',
    'Assistive technology or context: ' . ($assistiveTechnology !== '' ? $assistiveTechnology : 'Not provided'),
    '',
    'Issue details:',
    $issueDetails,
]);

$ticketRef = ajj_contact_generate_ticket_ref();
$submittedAt = gmdate('Y-m-d H:i:s');
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
        ':inquiry_type' => 'general',
        ':full_name' => $fullName,
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
        'Submission received from accessibility statement form.',
        'public-accessibility-form'
    );
    $savedToDb = true;
} catch (Throwable $e) {
    error_log('Accessibility inbox DB write failed for ' . $normalizedEmail . ': ' . $e->getMessage());
}

$savedToFile = false;
if (!$savedToDb) {
    $storageDir = __DIR__ . '/storage';
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0755, true);
    }

    $submission = [
        'ticket_ref' => $ticketRef,
        'submitted_at' => $submittedAt,
        'source' => 'accessibility-page',
        'name' => $fullName,
        'email' => $normalizedEmail,
        'affected_url' => $affectedUrl,
        'issue_type' => $issueTypeLabel,
        'issue_severity' => $issueSeverityLabel,
        'assistive_technology' => $assistiveTechnology,
        'issue_details' => $issueDetails,
        'subject' => $subject,
        'message' => $message,
        'ip' => $ipAddress,
        'user_agent' => $userAgent,
        'db_saved' => false,
    ];

    $line = json_encode($submission, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($line !== false) {
        $savedToFile = file_put_contents($storageDir . '/accessibility-submissions.ndjson', $line . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
    }
    if ($savedToFile) {
        error_log('Accessibility inbox fallback file write used for ticket ' . $ticketRef . '.');
    }
}

if (!$savedToDb && !$savedToFile) {
    $_SESSION['accessibility_errors'] = ['form' => 'We could not save your report right now. Please try again shortly.'];
    $_SESSION['accessibility_old'] = $oldInput;
    $redirect($accessibilityPath);
}

unset($_SESSION['accessibility_old'], $_SESSION['accessibility_errors']);
$_SESSION['accessibility_token'] = bin2hex(random_bytes(32));
$_SESSION['thank_you_payload'] = [
    'source' => 'accessibility',
    'title' => 'Accessibility Report Received',
    'message' => 'Thank you for helping improve access. Your report has been submitted successfully.',
    'reference' => $ticketRef,
];
$redirect($thankYouPath);
