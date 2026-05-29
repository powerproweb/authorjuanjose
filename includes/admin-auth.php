<?php
declare(strict_types=1);

/**
 * Independent admin authentication.
 *
 * Uses a fixed credential pair and supports:
 * - HTTP Basic auth when headers are available
 * - Session form login fallback when hosting strips auth headers
 */
function ajj_admin_auth_expected_user(): string
{
    return 'authorjuanjose';
}

function ajj_admin_auth_expected_hash(): string
{
    return '$2y$12$EnHrrxod3wmNylQgc/EZP.5CauS1/AKp.0dcHvTnh3VX.XZr.Qfui';
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
function ajj_admin_auth_render_login_form(string $realm, string $error = ''): never
{
    http_response_code(200);
    $safeRealm = htmlspecialchars($realm, ENT_QUOTES, 'UTF-8');
    $safeError = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?php echo $safeRealm; ?></title>
  <style>
    :root {
      --bg: #f5efe2;
      --panel: #fffaf1;
      --ink: #2b241d;
      --ink-light: #5c4f3d;
      --accent: #9d6a2f;
      --accent-hover: #b87a34;
      --border: #d8c8ae;
      --border-dark: #b5a48a;
      --danger: #9e3030;
      --font-body: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(160deg, rgba(43,36,29,.06), rgba(157,106,47,.08)), var(--bg);
      color: var(--ink);
      font-family: var(--font-body);
      line-height: 1.5;
    }
    .panel {
      width: min(460px, 92%);
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 1.5rem;
      box-shadow: 0 4px 12px rgba(43,36,29,.12);
    }
    h1 {
      margin: 0 0 .5rem;
      font-size: 1.35rem;
      text-align: center;
    }
    p {
      margin: 0 0 1rem;
      color: var(--ink-light);
      text-align: center;
    }
    label {
      display: block;
      font-weight: 600;
      font-size: .9rem;
      margin: .75rem 0 .3rem;
    }
    input {
      width: 100%;
      padding: .62rem .72rem;
      font-size: 1rem;
      border: 1px solid var(--border-dark);
      border-radius: 6px;
      background: #fff;
      color: var(--ink);
    }
    input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(157,106,47,.18);
    }
    button {
      width: 100%;
      margin-top: 1rem;
      padding: .7rem;
      border: 0;
      border-radius: 6px;
      background: var(--accent);
      color: #fff;
      font-weight: 700;
      cursor: pointer;
    }
    button:hover { background: var(--accent-hover); }
    .error {
      margin: 0 0 .8rem;
      padding: .6rem .75rem;
      border-radius: 6px;
      border: 1px solid #e5b3b3;
      background: #fdeeee;
      color: #7f2323;
      font-size: .9rem;
      text-align: center;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="panel">
    <h1><?php echo $safeRealm; ?></h1>
    <p>Enter admin credentials to continue.</p>
    <?php if ($safeError !== ''): ?>
      <div class="error"><?php echo $safeError; ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="on">
      <input type="hidden" name="_admin_login" value="1">
      <label for="admin_user">Username</label>
      <input id="admin_user" name="admin_user" type="text" required autocomplete="username">
      <label for="admin_pass">Password</label>
      <input id="admin_pass" name="admin_pass" type="password" required autocomplete="current-password">
      <button type="submit">Sign In</button>
    </form>
  </div>
</body>
</html>
<?php
    exit;
}

function ajj_require_admin_auth(string $realm = 'AuthorJuanJose Admin'): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $expectedUser = ajj_admin_auth_expected_user();
    $expectedHash = ajj_admin_auth_expected_hash();

    if (isset($_SESSION['admin_auth_user']) && is_string($_SESSION['admin_auth_user'])) {
        if (hash_equals($expectedUser, $_SESSION['admin_auth_user'])) {
            return $_SESSION['admin_auth_user'];
        }
        unset($_SESSION['admin_auth_user'], $_SESSION['admin_auth']);
    }

    $attempted = false;
    $providedUser = '';
    $providedPass = '';

    [$headerUser, $headerPass] = ajj_admin_auth_credentials();
    if ($headerUser !== '' || $headerPass !== '') {
        $providedUser = $headerUser;
        $providedPass = $headerPass;
        $attempted = true;
    }

    $isFormAttempt = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_admin_login']);
    if ($isFormAttempt) {
        $providedUser = trim((string)($_POST['admin_user'] ?? ''));
        $providedPass = (string)($_POST['admin_pass'] ?? '');
        $attempted = true;
    }

    $userMatches = $providedUser !== '' && hash_equals($expectedUser, $providedUser);
    $passMatches = $providedPass !== '' && password_verify($providedPass, $expectedHash);

    if ($attempted && $userMatches && $passMatches) {
        $_SESSION['admin_auth_user'] = $providedUser;
        $_SESSION['admin_auth'] = true;

        if ($isFormAttempt) {
            $redirectTo = $_SERVER['REQUEST_URI'] ?? '/admin/';
            header('Location: ' . $redirectTo, true, 303);
            exit;
        }

        return $providedUser;
    }

    $error = $attempted ? 'Invalid credentials. Please try again.' : '';
    ajj_admin_auth_render_login_form($realm, $error);
}
