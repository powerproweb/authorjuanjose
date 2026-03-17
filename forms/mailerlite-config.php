<?php
declare(strict_types=1);

/**
 * MailerLite integration configuration.
 *
 * Configure these values as environment variables in cPanel:
 * - MAILERLITE_API_KEY
 * - MAILERLITE_API_BASE (optional, defaults to https://connect.mailerlite.com/api)
 * - MAILERLITE_GROUP_ID_ARC (optional)
 * - MAILERLITE_GROUP_ID_ARC_ENGLISH (optional)
 * - MAILERLITE_GROUP_ID_ARC_SPANISH (optional)
 * - MAILERLITE_FIELD_KEY_REFERRAL (optional custom field key)
 * - MAILERLITE_FIELD_KEY_INTERESTS (optional custom field key)
 * - MAILERLITE_FIELD_KEY_LANGUAGE (optional custom field key)
 * - MAILERLITE_REQUEST_TIMEOUT (optional, defaults to 12 seconds)
 */

$envValue = static function (string $key, string $default = ''): string {
    $value = getenv($key);

    if ($value === false && array_key_exists($key, $_SERVER)) {
        $value = (string)$_SERVER[$key];
    }

    if ($value === false && array_key_exists($key, $_ENV)) {
        $value = (string)$_ENV[$key];
    }

    return $value === false ? $default : trim((string)$value);
};

return [
    'api_key' => $envValue('MAILERLITE_API_KEY'),
    'api_base' => rtrim($envValue('MAILERLITE_API_BASE', 'https://connect.mailerlite.com/api'), '/'),
    'request_timeout' => max(5, (int)$envValue('MAILERLITE_REQUEST_TIMEOUT', '12')),

    // Group segmentation
    'group_id_arc' => $envValue('MAILERLITE_GROUP_ID_ARC'),
    'group_id_english' => $envValue('MAILERLITE_GROUP_ID_ARC_ENGLISH'),
    'group_id_spanish' => $envValue('MAILERLITE_GROUP_ID_ARC_SPANISH'),

    // Optional custom field keys
    'field_key_referral' => $envValue('MAILERLITE_FIELD_KEY_REFERRAL'),
    'field_key_interests' => $envValue('MAILERLITE_FIELD_KEY_INTERESTS'),
    'field_key_language' => $envValue('MAILERLITE_FIELD_KEY_LANGUAGE'),
];
