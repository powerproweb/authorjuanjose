<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$flashSuccess = $_SESSION['accessibility_success'] ?? '';
$flashErrors  = $_SESSION['accessibility_errors'] ?? [];
$flashOld     = $_SESSION['accessibility_old'] ?? [];
unset($_SESSION['accessibility_success'], $_SESSION['accessibility_errors'], $_SESSION['accessibility_old']);

if (!isset($_SESSION['accessibility_token']) || !is_string($_SESSION['accessibility_token']) || $_SESSION['accessibility_token'] === '') {
    $_SESSION['accessibility_token'] = bin2hex(random_bytes(32));
}
$formToken = (string)$_SESSION['accessibility_token'];

$old = static function (string $key) use ($flashOld): string {
    return htmlspecialchars((string)($flashOld[$key] ?? ''), ENT_QUOTES, 'UTF-8');
};
$err = static function (string $key) use ($flashErrors): string {
    return (string)($flashErrors[$key] ?? '');
};

$page_title = 'Accessibility Statement | AuthorJuanJose.io';
$page_description = 'Accessibility commitments and contact pathway for AuthorJuanJose.io.';
require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="container page-shell">
  <section class="section">
    <p class="section-label">Accessibility</p>
    <h1>Accessibility Statement</h1>
    <p>AuthorJuanJose.io is designed to be usable by as many readers as possible, including readers using assistive technologies.</p>
    <p>Current baseline targets include keyboard-accessible navigation, visible focus indicators, semantic landmarks, reduced-motion support, and readable color contrast for primary content.</p>
  </section>

  <section class="section">
    <p class="section-label">Scope</p>
    <h2>What We Are Maintaining</h2>
    <ul class="benefit-list">
      <li>Consistent heading hierarchy and one primary page heading per page</li>
      <li>Keyboard operability for menus, forms, and interactive components</li>
      <li>Form labels and error visibility for required fields</li>
      <li>Reduced-motion fallback behavior for users who request it</li>
      <li>Ongoing review for contrast and readability issues</li>
    </ul>
  </section>

  <section class="section">
    <p class="section-label">Report an Issue</p>
    <h2>Need Help Accessing Content?</h2>
    <p>If you encounter an accessibility barrier, use this report form so the issue can be triaged quickly in the admin panel.</p>

    <?php if ($flashSuccess !== ''): ?>
      <div class="alert alert--success" role="status"><?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($err('form') !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($err('form'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="/forms/submit-accessibility" id="accessibility-report-form" novalidate>
      <input type="hidden" name="_token" value="<?php echo htmlspecialchars($formToken, ENT_QUOTES, 'UTF-8'); ?>">
      <div class="hp-field" aria-hidden="true">
        <label for="company">Company</label>
        <input id="company" name="company" type="text" tabindex="-1" autocomplete="off">
      </div>

      <div class="form-group">
        <label for="full_name">Your Name <span class="text-accent">*</span></label>
        <input id="full_name" name="full_name" type="text" required autocomplete="name" placeholder="Full name" value="<?php echo $old('full_name'); ?>">
        <?php if ($err('full_name') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($err('full_name'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="email">Email Address <span class="text-accent">*</span></label>
        <input id="email" name="email" type="email" required autocomplete="email" placeholder="you@example.com" value="<?php echo $old('email'); ?>">
        <?php if ($err('email') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($err('email'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="affected_url">Affected Page URL or Path <span class="text-accent">*</span></label>
        <input id="affected_url" name="affected_url" type="text" required placeholder="https://authorjuanjose.io/page or /page" value="<?php echo $old('affected_url'); ?>">
        <?php if ($err('affected_url') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($err('affected_url'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="issue_type">Issue Type <span class="text-accent">*</span></label>
        <select id="issue_type" name="issue_type" required>
          <option value="" disabled <?php echo $old('issue_type') === '' ? 'selected' : ''; ?>>Select a category</option>
          <option value="keyboard-navigation" <?php echo $old('issue_type') === 'keyboard-navigation' ? 'selected' : ''; ?>>Keyboard navigation</option>
          <option value="screen-reader" <?php echo $old('issue_type') === 'screen-reader' ? 'selected' : ''; ?>>Screen reader support</option>
          <option value="contrast-visual" <?php echo $old('issue_type') === 'contrast-visual' ? 'selected' : ''; ?>>Contrast or visual clarity</option>
          <option value="form-control" <?php echo $old('issue_type') === 'form-control' ? 'selected' : ''; ?>>Form label or control behavior</option>
          <option value="media-caption" <?php echo $old('issue_type') === 'media-caption' ? 'selected' : ''; ?>>Media captions or transcripts</option>
          <option value="motion-animation" <?php echo $old('issue_type') === 'motion-animation' ? 'selected' : ''; ?>>Motion or animation sensitivity</option>
          <option value="other" <?php echo $old('issue_type') === 'other' ? 'selected' : ''; ?>>Other accessibility barrier</option>
        </select>
        <?php if ($err('issue_type') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($err('issue_type'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="issue_severity">Impact Level <span class="text-accent">*</span></label>
        <select id="issue_severity" name="issue_severity" required>
          <option value="" disabled <?php echo $old('issue_severity') === '' ? 'selected' : ''; ?>>Select impact level</option>
          <option value="low" <?php echo $old('issue_severity') === 'low' ? 'selected' : ''; ?>>Low impact</option>
          <option value="medium" <?php echo $old('issue_severity') === 'medium' ? 'selected' : ''; ?>>Medium impact</option>
          <option value="high" <?php echo $old('issue_severity') === 'high' ? 'selected' : ''; ?>>High impact</option>
          <option value="critical" <?php echo $old('issue_severity') === 'critical' ? 'selected' : ''; ?>>Critical blocker</option>
        </select>
        <?php if ($err('issue_severity') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($err('issue_severity'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="assistive_technology">Assistive Technology or Device Context (optional)</label>
        <input id="assistive_technology" name="assistive_technology" type="text" placeholder="Examples: NVDA + Firefox on Windows 11" value="<?php echo $old('assistive_technology'); ?>">
        <?php if ($err('assistive_technology') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($err('assistive_technology'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="issue_details">Issue Details <span class="text-accent">*</span></label>
        <textarea id="issue_details" name="issue_details" required placeholder="Describe what happened, what you expected, and how this affected access."><?php echo $old('issue_details'); ?></textarea>
        <?php if ($err('issue_details') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($err('issue_details'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label>
          <input type="checkbox" name="consent_contact" value="1" <?php echo $old('consent_contact') === '1' ? 'checked' : ''; ?> required>
          I allow the team to contact me for follow-up details on this report.
        </label>
        <?php if ($err('consent_contact') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($err('consent_contact'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <p class="mt-lg">
        <button class="button button--lg" type="submit">Submit Accessibility Report</button>
      </p>
    </form>

    <p class="mt-lg">If you prefer, you can still use the general contact form for accessibility questions.</p>
    <p><a class="button button--outline" href="/contact">Open Contact Page</a></p>
  </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
