<?php
declare(strict_types=1);

/**
 * Independent admin authentication.
 *
 * Supports environment overrides:
 *   AJJ_ADMIN_USER
 *   AJJ_ADMIN_PASS_HASH
 */
function ajj_admin_auth_expected_user(): string
{
    $envUser = trim((string)getenv('AJJ_ADMIN_USER'));
    return $envUser !== '' ? $envUser : 'adminjuanjose';
}

function ajj_admin_auth_expected_hash(): string
{
    $envHash = trim((string)getenv('AJJ_ADMIN_PASS_HASH'));
    return $envHash !== '' ? $envHash : '$2y$12$T0.dZ0XkZmUMTJ6kSXC/sux9Bg4kp9oPb9O3a.M4bYaAf549jtdW2';
}

function ajj_admin_auth_header_value(): string
{
    $candidates = [
        $_SERVER['HTTP_AUTHORIZATION'] ?? null,
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
        $_SERVER['Authorization'] ?? null,
        $_SERVER['REDIRECT_Authorization'] ?? null,
    ];

    foreach ($candidates as $value) {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }

    return '';
}

function ajj_admin_auth_parse_basic_header(string $headerValue): array
{
    if ($headerValue === '' || stripos($headerValue, 'basic ') !== 0) {
        return ['', ''];
    }

    $decoded = base64_decode(substr($headerValue, 6), true);
    if (!is_string($decoded) || strpos($decoded, ':') === false) {
        return ['', ''];
    }

    [$user, $pass] = explode(':', $decoded, 2);
    return [trim($user), $pass];
}

function ajj_admin_auth_credentials(): array
{
    $user = (string)($_SERVER['PHP_AUTH_USER'] ?? '');
    $pass = (string)($_SERVER['PHP_AUTH_PW'] ?? '');

    if ($user === '' || $pass === '') {
        [$parsedUser, $parsedPass] = ajj_admin_auth_parse_basic_header(ajj_admin_auth_header_value());
        if ($parsedUser !== '' || $parsedPass !== '') {
            $user = $parsedUser;
            $pass = $parsedPass;
        }
    }

    return [$user, $pass];
}

function ajj_require_admin_auth(string $realm = 'AuthorJuanJose Admin'): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    [$providedUser, $providedPass] = ajj_admin_auth_credentials();
    $expectedUser = ajj_admin_auth_expected_user();
    $expectedHash = ajj_admin_auth_expected_hash();

    $userMatches = hash_equals($expectedUser, $providedUser);
    $passMatches = $providedPass !== '' && password_verify($providedPass, $expectedHash);

    if (!$userMatches || !$passMatches) {
        header('WWW-Authenticate: Basic realm="' . str_replace('"', '', $realm) . '"');
        http_response_code(401);
        exit('Unauthorized');
    }

    $_SESSION['admin_auth_user'] = $providedUser;
    return $providedUser;
}
