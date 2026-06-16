<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Redirect back to referring page
$referer = $_SERVER['HTTP_REFERER'] ?? '/';
$redirect_to = parse_url($referer, PHP_URL_PATH) ?: '/';
$thankYouPath = '/thank-you?source=newsletter';

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
    $_SESSION['thank_you_payload'] = [
        'source' => 'newsletter',
        'title' => 'Subscription Confirmed',
        'message' => 'Thank you for subscribing. You are on the list for future updates.',
    ];
    $redirect($thankYouPath);
}

$email    = trim((string)($_POST['email'] ?? ''));
$name     = trim((string)($_POST['name'] ?? ''));
require_once dirname(__DIR__) . '/includes/db.php';
$listSlugs = $_POST['list_slugs'] ?? [];
if (!is_array($listSlugs)) {
    $listSlugs = [$listSlugs];
}

// Backward compatibility for old single-select form submissions
$legacyInterest = strtolower(trim((string)($_POST['interest'] ?? '')));
if ($legacyInterest !== '') {
    $legacyMapped = map_legacy_newsletter_interest_to_list_slugs($legacyInterest);
    $listSlugs = array_merge($listSlugs, $legacyMapped);
}

// Validate
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['newsletter_error'] = 'Please provide a valid email address.';
    $redirect($redirect_to);
}

// Insert into database
$pdo = get_db();
$storageDir = __DIR__ . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$ipAddress = trim((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
$rateLimitFile = $storageDir . '/newsletter-rate-limit.json';
$rateLimitWindowSeconds = 3600;
$rateLimitMaxAttempts = 18;
$rateLimitKey = hash('sha256', 'newsletter|' . $ipAddress);
$rateData = [];
if (is_file($rateLimitFile)) {
    $json = file_get_contents($rateLimitFile);
    if (is_string($json) && $json !== '') {
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            $rateData = $decoded;
        }
    }
}
$now = time();
$entry = $rateData[$rateLimitKey] ?? ['window_start' => $now, 'hits' => 0];
$windowStart = (int)($entry['window_start'] ?? $now);
$hits = (int)($entry['hits'] ?? 0);
if (($now - $windowStart) >= $rateLimitWindowSeconds) {
    $windowStart = $now;
    $hits = 0;
}
$hits++;
$rateData[$rateLimitKey] = [
    'window_start' => $windowStart,
    'hits' => $hits,
];
foreach ($rateData as $key => $value) {
    $keyWindowStart = (int)($value['window_start'] ?? 0);
    if ($keyWindowStart <= 0 || ($now - $keyWindowStart) >= ($rateLimitWindowSeconds * 2)) {
        unset($rateData[$key]);
    }
}
file_put_contents(
    $rateLimitFile,
    json_encode($rateData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    LOCK_EX
);
if ($hits > $rateLimitMaxAttempts) {
    $_SESSION['newsletter_error'] = 'Too many signup attempts. Please wait and try again.';
    $redirect($redirect_to);
}

$normalizedEmail = normalize_mailing_email($email);
$listSlugs = normalize_mailing_list_slugs($listSlugs);
if ($listSlugs === []) {
    $listSlugs = ['author-news'];
}

$activeListMap = get_active_mailing_list_ids_by_slug($pdo);
$selectedListIds = [];
foreach ($listSlugs as $slug) {
    $listId = $activeListMap[$slug] ?? 0;
    if ($listId > 0) {
        $selectedListIds[$listId] = true;
    }
}

if ($selectedListIds === []) {
    $_SESSION['newsletter_error'] = 'Unable to subscribe right now. Please try again shortly.';
    $redirect($redirect_to);
}

$contactLookup = $pdo->prepare('SELECT id FROM mailing_contacts WHERE email = ?');
$contactLookup->execute([$normalizedEmail]);
$contactId = (int)$contactLookup->fetchColumn();
$isNewContact = $contactId <= 0;

if ($isNewContact) {
    $pdo->prepare('
        INSERT INTO mailing_contacts (email, name, status, source, updated_at)
        VALUES (?, ?, "active", "newsletter_form", CURRENT_TIMESTAMP)
    ')->execute([$normalizedEmail, $name]);
    $contactId = (int)$pdo->lastInsertId();
} elseif ($name !== '') {
    $pdo->prepare('
        UPDATE mailing_contacts
        SET name = ?, status = "active", updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ')->execute([$name, $contactId]);
} else {
    $pdo->prepare('
        UPDATE mailing_contacts
        SET status = "active", updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ')->execute([$contactId]);
}

$reactivateMembership = $pdo->prepare('
    UPDATE mailing_list_memberships
    SET status = "active", unsubscribed_at = NULL, updated_at = CURRENT_TIMESTAMP
    WHERE contact_id = ? AND list_id = ?
');
$insertMembership = $pdo->prepare('
    INSERT OR IGNORE INTO mailing_list_memberships (contact_id, list_id, status, source)
    VALUES (?, ?, "active", "newsletter_form")
');

foreach (array_keys($selectedListIds) as $listId) {
    $reactivateMembership->execute([$contactId, $listId]);
    $insertMembership->execute([$contactId, $listId]);
}

// Keep legacy table in sync while new list system is rolled out
$hasFiction = isset($activeListMap['fiction']) && isset($selectedListIds[$activeListMap['fiction']]);
$hasNonFiction = isset($activeListMap['non-fiction']) && isset($selectedListIds[$activeListMap['non-fiction']]);
$derivedLegacyInterest = $hasFiction && $hasNonFiction
    ? 'both'
    : ($hasNonFiction ? 'non-fiction' : 'fiction');

$legacyStmt = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE email = ?');
$legacyStmt->execute([$normalizedEmail]);
if ($legacyStmt->fetch()) {
    $pdo->prepare('UPDATE newsletter_subscribers SET name = ?, interest = ? WHERE email = ?')
        ->execute([$name, $derivedLegacyInterest, $normalizedEmail]);
} else {
    $pdo->prepare('INSERT INTO newsletter_subscribers (email, name, interest) VALUES (?, ?, ?)')
        ->execute([$normalizedEmail, $name, $derivedLegacyInterest]);
}

if ($isNewContact) {
    // Queue welcome email for first-time contact creation
    $pdo->prepare('INSERT INTO email_queue (recipient_email, recipient_name, template_key, language) VALUES (?, ?, ?, ?)')
        ->execute([$normalizedEmail, $name, 'newsletter_welcome_1', 'English']);
}

// Log
$line = json_encode([
    'event'   => 'newsletter_signup',
    'email'   => $normalizedEmail,
    'name'    => $name,
    'list_slugs' => $listSlugs,
    'at'      => gmdate('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($line !== false) {
    file_put_contents($storageDir . '/newsletter-signups.ndjson', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$_SESSION['thank_you_payload'] = [
    'source' => 'newsletter',
    'title' => 'Subscription Confirmed',
    'message' => 'You are subscribed. Welcome aboard.',
];
$redirect($thankYouPath);
