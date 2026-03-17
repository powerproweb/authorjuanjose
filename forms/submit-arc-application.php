<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$joinPath = '/arc-reader-club/join';
$thankYouPath = '/arc-reader-club/thank-you';

$redirect = static function (string $url): void {
    header('Location: ' . $url, true, 303);
    exit;
};

$setFlashAndRedirect = static function (array $errors, array $old, string $url) use ($redirect): void {
    $_SESSION['arc_form_errors'] = $errors;
    $_SESSION['arc_form_old'] = $old;
    $redirect($url);
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$honeypot = trim((string)($_POST['website'] ?? ''));
if ($honeypot !== '') {
    $_SESSION['arc_form_success'] = 'Application received. Thank you for your interest in the ARC Reader Club.';
    $redirect($thankYouPath);
}

$token = (string)($_POST['_token'] ?? '');
$sessionToken = (string)($_SESSION['arc_form_token'] ?? '');

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$language = trim((string)($_POST['language'] ?? ''));
$country = trim((string)($_POST['country'] ?? ''));
$referral = trim((string)($_POST['referral'] ?? ''));
$interests = trim((string)($_POST['interests'] ?? ''));
$consent = (string)($_POST['consent'] ?? '') === '1';

$oldInput = [
    'name' => $name,
    'email' => $email,
    'language' => $language,
    'country' => $country,
    'referral' => $referral,
    'interests' => $interests,
    'consent' => $consent ? '1' : '',
];

$errors = [];
$strLength = static function (string $value): int {
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
};

if ($sessionToken === '' || $token === '' || !hash_equals($sessionToken, $token)) {
    $errors['form'] = 'Your session expired. Please refresh the page and try again.';
}
if ($name === '' || $strLength($name) < 2 || $strLength($name) > 120) {
    $errors['name'] = 'Please enter your full name (2-120 characters).';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please provide a valid email address.';
}

$allowedLanguages = ['English', 'Spanish'];
if (!in_array($language, $allowedLanguages, true)) {
    $errors['language'] = 'Please select a valid preferred language.';
}

if ($strLength($country) > 100) {
    $errors['country'] = 'Country is too long. Please keep it under 100 characters.';
}
if ($strLength($referral) > 150) {
    $errors['referral'] = 'Referral field is too long. Please keep it under 150 characters.';
}
if ($strLength($interests) > 2000) {
    $errors['interests'] = 'Reading interests are too long. Please keep it under 2000 characters.';
}

if (!$consent) {
    $errors['consent'] = 'You must agree to the honest review and privacy policy terms.';
}

if ($errors !== []) {
    $setFlashAndRedirect($errors, $oldInput, $joinPath . '?status=error');
}

$config = require __DIR__ . '/mailerlite-config.php';

$storageDir = __DIR__ . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$submission = [
    'submitted_at' => gmdate('Y-m-d H:i:s'),
    'name' => $name,
    'email' => strtolower($email),
    'language' => $language,
    'country' => $country,
    'referral' => $referral,
    'interests' => $interests,
    'consent' => true,
    'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
    'user_agent' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
];

$appendLogRecord = static function (array $record) use ($storageDir): void {
    $filePath = $storageDir . '/arc-applications.ndjson';
    $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($line !== false) {
        file_put_contents($filePath, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
};

$buildGroups = static function (array $cfg, string $lang): array {
    $groups = [];

    if ($cfg['group_id_arc'] !== '') {
        $groups[] = $cfg['group_id_arc'];
    }
    if ($lang === 'English' && $cfg['group_id_english'] !== '') {
        $groups[] = $cfg['group_id_english'];
    }
    if ($lang === 'Spanish' && $cfg['group_id_spanish'] !== '') {
        $groups[] = $cfg['group_id_spanish'];
    }

    return array_values(array_unique(array_filter($groups, static fn ($id): bool => $id !== '')));
};

$buildFields = static function (array $cfg, array $data): array {
    $fields = ['name' => $data['name']];

    if ($data['country'] !== '') {
        $fields['country'] = $data['country'];
    }
    if ($cfg['field_key_referral'] !== '' && $data['referral'] !== '') {
        $fields[$cfg['field_key_referral']] = $data['referral'];
    }
    if ($cfg['field_key_interests'] !== '' && $data['interests'] !== '') {
        $fields[$cfg['field_key_interests']] = $data['interests'];
    }
    if ($cfg['field_key_language'] !== '') {
        $fields[$cfg['field_key_language']] = $data['language'];
    }

    return $fields;
};

$sendMailerLiteUpsert = static function (array $cfg, array $data) use ($buildGroups, $buildFields): array {
    if ($cfg['api_key'] === '') {
        return [
            'ok' => false,
            'status_code' => 0,
            'message' => 'MailerLite API key is not configured.',
            'response_body' => '',
        ];
    }

    $payloadArray = [
        'email' => $data['email'],
        'fields' => $buildFields($cfg, $data),
        'groups' => $buildGroups($cfg, $data['language']),
        'status' => 'active',
        'subscribed_at' => gmdate('Y-m-d H:i:s'),
    ];

    $payload = json_encode($payloadArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        return [
            'ok' => false,
            'status_code' => 0,
            'message' => 'Failed to encode MailerLite payload.',
            'response_body' => '',
        ];
    }

    $url = $cfg['api_base'] . '/subscribers';
    $headers = [
        'Authorization: Bearer ' . $cfg['api_key'],
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    $statusCode = 0;
    $responseBody = '';
    $errorMessage = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $cfg['request_timeout'],
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $responseBody = (string)curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $errorMessage = curl_error($ch);
        }
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $payload,
                'timeout' => (int)$cfg['request_timeout'],
                'ignore_errors' => true,
            ],
        ]);

        $responseBody = (string)file_get_contents($url, false, $context);

        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $headerLine) {
                if (preg_match('#HTTP/\d\.\d\s+(\d{3})#', $headerLine, $matches) === 1) {
                    $statusCode = (int)$matches[1];
                    break;
                }
            }
        }
    }

    $ok = in_array($statusCode, [200, 201], true);

    return [
        'ok' => $ok,
        'status_code' => $statusCode,
        'message' => $ok ? 'MailerLite subscriber upsert successful.' : ($errorMessage !== '' ? $errorMessage : 'MailerLite API request failed.'),
        'response_body' => $responseBody,
    ];
};

$appendLogRecord([
    'event' => 'arc_application_received',
    'submission' => $submission,
]);

$mailerliteResult = $sendMailerLiteUpsert($config, $submission);

$appendLogRecord([
    'event' => 'arc_application_mailerlite_result',
    'submission_email' => $submission['email'],
    'status_code' => $mailerliteResult['status_code'],
    'ok' => $mailerliteResult['ok'],
    'message' => $mailerliteResult['message'],
    'response_body' => $mailerliteResult['response_body'],
]);

unset($_SESSION['arc_form_old'], $_SESSION['arc_form_errors']);
$_SESSION['arc_form_token'] = bin2hex(random_bytes(32));

if ($mailerliteResult['ok']) {
    $_SESSION['arc_form_success'] = 'Application received. Welcome to the ARC Reader Club journey.';
    $redirect($thankYouPath);
}

$_SESSION['arc_form_notice'] = 'Application received. Our team will complete processing shortly.';
error_log('ARC application MailerLite sync failed for ' . $submission['email'] . ' with status ' . (string)$mailerliteResult['status_code'] . '.');
$redirect($thankYouPath . '?status=queued');
