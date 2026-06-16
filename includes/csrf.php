<?php
declare(strict_types=1);

function csrf_token(string $scope): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['_csrf_tokens']) || !is_array($_SESSION['_csrf_tokens'])) {
        $_SESSION['_csrf_tokens'] = [];
    }
    if (!isset($_SESSION['_csrf_tokens'][$scope]) || !is_string($_SESSION['_csrf_tokens'][$scope])) {
        $_SESSION['_csrf_tokens'][$scope] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_tokens'][$scope];
}

function csrf_validate(string $scope, ?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $provided = (string)($token ?? '');
    $stored = (string)($_SESSION['_csrf_tokens'][$scope] ?? '');
    if ($provided === '' || $stored === '') {
        return false;
    }

    $isValid = hash_equals($stored, $provided);
    if ($isValid) {
        $_SESSION['_csrf_tokens'][$scope] = bin2hex(random_bytes(32));
    }

    return $isValid;
}
