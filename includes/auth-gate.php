<?php
declare(strict_types=1);

/**
 * Site-wide auth gate.
 *
 * Include this at the top of header.php. While enabled, all visitors see a
 * styled "under construction" page with a login form. Authenticated admins
 * browse the site normally.
 *
 * To DISABLE this gate when the site goes public, either:
 *   - Delete or rename this file, or
 *   - Set the constant SITE_AUTH_GATE_ENABLED to false before including header.php
 */

if (defined('SITE_AUTH_GATE_ENABLED') && SITE_AUTH_GATE_ENABLED === false) {
    return;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// --- credentials (username + bcrypt hash) ---
$_authUser = 'adminjuanjose';
$_authHash = '$2y$12$T0.dZ0XkZmUMTJ6kSXC/sux9Bg4kp9oPb9O3a.M4bYaAf549jtdW2';

// Already authenticated?
if (isset($_SESSION['site_auth']) && $_SESSION['site_auth'] === true) {
    // Handle logout
    if (isset($_GET['site_logout'])) {
        $_SESSION['site_auth'] = false;
        session_destroy();
        header('Location: /', true, 303);
        exit;
    }
    return; // Let the page render normally
}

// Process login attempt
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_site_login'])) {
    $inputUser = trim((string)($_POST['site_user'] ?? ''));
    $inputPass = (string)($_POST['site_pass'] ?? '');

    if ($inputUser === $_authUser && password_verify($inputPass, $_authHash)) {
        $_SESSION['site_auth'] = true;
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'), true, 303);
        exit;
    }

    $loginError = 'Invalid credentials. Please try again.';
}

// --- Render the gate page and stop ---
http_response_code(200);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>AuthorJuanJose.io — Coming Soon</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
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
      --font-heading: 'Playfair Display', Georgia, serif;
      --font-body: 'Source Sans 3', 'Segoe UI', sans-serif;
    }

    *, *::before, *::after { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: var(--font-body);
      background:
        linear-gradient(160deg, rgba(43,36,29,.06), rgba(157,106,47,.08)),
        var(--bg);
      color: var(--ink);
      line-height: 1.6;
    }

    .gate {
      width: min(520px, 90%);
      text-align: center;
      padding: 2.5rem 2rem;
    }

    .gate-brand {
      font-family: var(--font-heading);
      font-size: 1.6rem;
      font-weight: 900;
      color: var(--ink);
      letter-spacing: 0.02em;
      margin-bottom: 0.5rem;
    }

    .gate-gears {
      color: var(--border-dark);
      font-size: 1.4rem;
      letter-spacing: 0.3em;
      margin: 1rem 0;
    }

    .gate h1 {
      font-family: var(--font-heading);
      font-size: clamp(1.6rem, 4vw, 2.2rem);
      font-weight: 700;
      margin: 0 0 0.75rem;
      color: var(--ink);
    }

    .gate p {
      color: var(--ink-light);
      font-size: 1.05rem;
      max-width: 36em;
      margin: 0 auto 1.5rem;
    }

    .gate-panel {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 0.5rem;
      padding: 1.5rem;
      margin-top: 1.5rem;
      text-align: left;
      box-shadow: 0 4px 12px rgba(43,36,29,.1);
    }

    .gate-panel h2 {
      font-family: var(--font-heading);
      font-size: 1.15rem;
      margin: 0 0 1rem;
      text-align: center;
    }

    .gate-field {
      margin-bottom: 0.85rem;
    }

    .gate-field label {
      display: block;
      font-weight: 600;
      font-size: 0.88rem;
      margin-bottom: 0.25rem;
    }

    .gate-field input {
      width: 100%;
      font-family: var(--font-body);
      font-size: 1rem;
      padding: 0.55rem 0.7rem;
      border: 1px solid var(--border-dark);
      border-radius: 0.25rem;
      background: #fff;
      color: var(--ink);
    }

    .gate-field input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(157,106,47,.18);
    }

    .gate-btn {
      display: block;
      width: 100%;
      font-family: var(--font-body);
      font-weight: 700;
      font-size: 0.95rem;
      padding: 0.7rem;
      border: none;
      border-radius: 0.25rem;
      background: var(--accent);
      color: #fff;
      cursor: pointer;
      transition: background .2s;
      margin-top: 0.5rem;
    }

    .gate-btn:hover { background: var(--accent-hover); }

    .gate-error {
      background: #fdeeee;
      border: 1px solid #e5b3b3;
      color: #7f2323;
      padding: 0.6rem 0.8rem;
      border-radius: 0.25rem;
      font-size: 0.88rem;
      font-weight: 600;
      margin-bottom: 0.85rem;
      text-align: center;
    }

    .gate-footer {
      margin-top: 2rem;
      font-size: 0.82rem;
      color: var(--border-dark);
    }
  </style>
</head>
<body>
  <div class="gate">
    <div class="gate-brand">AuthorJuanJose.io</div>
    <div class="gate-gears">&#9881; &#9881; &#9881;</div>
    <h1>Something Extraordinary Is Being Built</h1>
    <p>The gears are turning and the steam is rising. AuthorJuanJose.io is currently under construction. A new world of steampunk science fiction, an exclusive ARC Reader Club, and much more is on its way.</p>
    <p>Check back soon &mdash; or if you have access, sign in below.</p>

    <div class="gate-panel">
      <h2>Authorized Access</h2>
      <?php if ($loginError !== ''): ?>
        <div class="gate-error"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="_site_login" value="1">
        <div class="gate-field">
          <label for="site_user">Username</label>
          <input id="site_user" name="site_user" type="text" required autocomplete="username">
        </div>
        <div class="gate-field">
          <label for="site_pass">Password</label>
          <input id="site_pass" name="site_pass" type="password" required autocomplete="current-password">
        </div>
        <button class="gate-btn" type="submit">Enter the Workshop</button>
      </form>
    </div>

    <div class="gate-footer">&copy; <?php echo date('Y'); ?> AuthorJuanJose.io &mdash; All rights reserved.</div>
  </div>
</body>
</html>
<?php
exit; // Stop page rendering — only the gate is shown
