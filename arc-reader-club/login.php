<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once dirname(__DIR__) . '/includes/db.php';

// Already logged in? Go to dashboard.
if (!empty($_SESSION['arc_member_id'])) {
    header('Location: /arc-reader-club/dashboard');
    exit;
}

$error = '';

// ---------------------------------------------------------------------------
//  Handle POST
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $pdo  = get_db();
        $stmt = $pdo->prepare('SELECT id, password_hash, status, name FROM members WHERE email = ?');
        $stmt->execute([$email]);
        $member = $stmt->fetch();

        if (!$member || $member['password_hash'] === '' || !password_verify($password, $member['password_hash'])) {
            $error = 'Invalid email or password.';
        } elseif ($member['status'] === 'pending') {
            $error = 'Your membership application is still under review. You will be notified when approved.';
        } elseif ($member['status'] === 'suspended') {
            $error = 'Your account has been suspended. Please contact us for more information.';
        } elseif ($member['status'] === 'active') {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            $_SESSION['arc_member_id']   = (int)$member['id'];
            $_SESSION['arc_member_name'] = $member['name'];

            header('Location: /arc-reader-club/dashboard');
            exit;
        } else {
            $error = 'Unable to log in at this time.';
        }
    }
}

$page_title = 'Login | ARC Reader Club';
$show_arc_sub_navigation = true;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">
  <div class="container--narrow" style="margin:0 auto">

    <h1>Member Login</h1>
    <p class="lead">Sign in to access your ARC Reader Club dashboard, missions, and distinctions.</p>

    <hr class="ornament-rule">

    <?php if ($error !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="/arc-reader-club/login" novalidate>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input id="email" name="email" type="email" required autocomplete="email" placeholder="you@example.com"
               value="<?php echo htmlspecialchars((string)($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Your password">
      </div>

      <p class="mt-lg">
        <button class="button button--lg" type="submit">Sign In</button>
      </p>

    </form>

    <p class="mt-lg" style="font-size:.9rem;color:var(--ink-light)">
      Not a member yet? <a href="/arc-reader-club/join">Apply to join the ARC Reader Club</a>.
    </p>

  </div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
