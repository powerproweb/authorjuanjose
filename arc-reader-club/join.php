<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$flashErrors = $_SESSION['arc_form_errors'] ?? [];
$flashOld = $_SESSION['arc_form_old'] ?? [];
$flashSuccess = $_SESSION['arc_form_success'] ?? '';
$flashNotice = $_SESSION['arc_form_notice'] ?? '';

unset($_SESSION['arc_form_errors'], $_SESSION['arc_form_old'], $_SESSION['arc_form_success'], $_SESSION['arc_form_notice']);

if (!isset($_SESSION['arc_form_token']) || !is_string($_SESSION['arc_form_token']) || $_SESSION['arc_form_token'] === '') {
    $_SESSION['arc_form_token'] = bin2hex(random_bytes(32));
}

$formToken = (string)$_SESSION['arc_form_token'];

$oldValue = static function (string $key) use ($flashOld): string {
    return htmlspecialchars((string)($flashOld[$key] ?? ''), ENT_QUOTES, 'UTF-8');
};

$fieldError = static function (string $key) use ($flashErrors): string {
    return (string)($flashErrors[$key] ?? '');
};

$page_title = 'Apply to Join the ARC Reader Club | AuthorJuanJose.io';
$show_arc_sub_navigation = true;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <div class="container--narrow" style="margin:0 auto">
    <h1>Apply to Join the ARC Reader Club</h1>
    <p class="lead">Ready to read early and support upcoming launches? Submit your application to join the official ARC Reader Club for Author Juan Jose. Qualified members will be added to the community and notified when new ARC opportunities become available.</p>

    <hr class="ornament-rule">

    <?php if ($flashSuccess !== ''): ?>
      <div class="alert alert--success" role="status"><?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($flashNotice !== ''): ?>
      <div class="alert alert--info" role="status"><?php echo htmlspecialchars($flashNotice, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($fieldError('form') !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($fieldError('form'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="/forms/submit-arc-application" id="arc-application-form" novalidate>
      <input type="hidden" name="_token" value="<?php echo htmlspecialchars($formToken, ENT_QUOTES, 'UTF-8'); ?>">
      <div class="hp-field" aria-hidden="true">
        <label for="website">Website</label>
        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
      </div>

      <div class="form-group">
        <label for="name">Full Name <span class="text-accent">*</span></label>
        <input id="name" name="name" type="text" required autocomplete="name" placeholder="Your full name" value="<?php echo $oldValue('name'); ?>">
        <?php if ($fieldError('name') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($fieldError('name'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="email">Email Address <span class="text-accent">*</span></label>
        <input id="email" name="email" type="email" required autocomplete="email" placeholder="you@example.com" value="<?php echo $oldValue('email'); ?>">
        <?php if ($fieldError('email') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($fieldError('email'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="language">Preferred Language <span class="text-accent">*</span></label>
        <select id="language" name="language" required>
          <option value="" disabled <?php echo $oldValue('language') === '' ? 'selected' : ''; ?>>Select a language</option>
          <option value="English" <?php echo $oldValue('language') === 'English' ? 'selected' : ''; ?>>English</option>
          <option value="Spanish" <?php echo $oldValue('language') === 'Spanish' ? 'selected' : ''; ?>>Spanish</option>
        </select>
        <?php if ($fieldError('language') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($fieldError('language'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="country">Country</label>
        <input id="country" name="country" type="text" autocomplete="country-name" placeholder="Optional — helps with future features" value="<?php echo $oldValue('country'); ?>">
        <small>Used for future geo-routing of review links and storefronts.</small>
        <?php if ($fieldError('country') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($fieldError('country'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="referral">How did you hear about us?</label>
        <input id="referral" name="referral" type="text" placeholder="Optional" value="<?php echo $oldValue('referral'); ?>">
        <?php if ($fieldError('referral') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($fieldError('referral'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="interests">What steampunk or sci-fi books do you enjoy?</label>
        <textarea id="interests" name="interests" placeholder="Optional — tell us about your reading interests"><?php echo $oldValue('interests'); ?></textarea>
        <?php if ($fieldError('interests') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($fieldError('interests'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <div class="checkbox-group">
          <input id="consent" name="consent" type="checkbox" value="1" required <?php echo $oldValue('consent') === '1' ? 'checked' : ''; ?>>
          <label for="consent">I commit to providing honest reviews when participating in ARC campaigns, and I have read and agree to the <a href="/privacy" target="_blank">privacy policy</a>. <span class="text-accent">*</span></label>
        </div>
        <?php if ($fieldError('consent') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($fieldError('consent'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <p class="mt-lg">
        <button class="button button--lg" type="submit">Submit Your Application</button>
      </p>

    </form>
  </div>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
