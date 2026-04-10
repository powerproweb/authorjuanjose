<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$flashSuccess = $_SESSION['contact_success'] ?? '';
$flashErrors  = $_SESSION['contact_errors'] ?? [];
$flashOld     = $_SESSION['contact_old'] ?? [];
unset($_SESSION['contact_success'], $_SESSION['contact_errors'], $_SESSION['contact_old']);

if (!isset($_SESSION['contact_token']) || !is_string($_SESSION['contact_token']) || $_SESSION['contact_token'] === '') {
    $_SESSION['contact_token'] = bin2hex(random_bytes(32));
}
$formToken = (string)$_SESSION['contact_token'];

$old = static function (string $key) use ($flashOld): string {
    return htmlspecialchars((string)($flashOld[$key] ?? ''), ENT_QUOTES, 'UTF-8');
};
$err = static function (string $key) use ($flashErrors): string {
    return (string)($flashErrors[$key] ?? '');
};

$page_title = 'Contact | AuthorJuanJose.io';
$page_description = 'Get in touch with Author Juan Jose. General inquiries, media requests, speaking engagements, and ARC Reader Club questions.';
require_once __DIR__ . '/includes/header.php';
?>

<main class="container page-shell">
  <div class="container--narrow" style="margin:0 auto">

    <h1>Contact</h1>
    <p class="lead">Have a question, media inquiry, speaking request, or just want to say hello? Use the form below to get in touch.</p>

    <hr class="ornament-rule">

    <?php if ($flashSuccess !== ''): ?>
      <div class="alert alert--success" role="status"><?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($err('form') !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($err('form'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="/forms/submit-contact" id="contact-form" novalidate>
      <input type="hidden" name="_token" value="<?php echo htmlspecialchars($formToken, ENT_QUOTES, 'UTF-8'); ?>">
      <div class="hp-field" aria-hidden="true">
        <label for="fax">Fax</label>
        <input id="fax" name="fax" type="text" tabindex="-1" autocomplete="off">
      </div>

      <div class="form-group">
        <label for="inquiry_type">Inquiry Type <span class="text-accent">*</span></label>
        <select id="inquiry_type" name="inquiry_type" required>
          <option value="" disabled <?php echo $old('inquiry_type') === '' ? 'selected' : ''; ?>>Select a category</option>
          <option value="general" <?php echo $old('inquiry_type') === 'general' ? 'selected' : ''; ?>>General Contact</option>
          <option value="media" <?php echo $old('inquiry_type') === 'media' ? 'selected' : ''; ?>>Media Inquiry</option>
          <option value="speaking" <?php echo $old('inquiry_type') === 'speaking' ? 'selected' : ''; ?>>Speaking / Event Inquiry</option>
          <option value="reader" <?php echo $old('inquiry_type') === 'reader' ? 'selected' : ''; ?>>Reader Message</option>
          <option value="arc" <?php echo $old('inquiry_type') === 'arc' ? 'selected' : ''; ?>>ARC Reader Club Question</option>
        </select>
        <?php if ($err('inquiry_type') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($err('inquiry_type'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="name">Your Name <span class="text-accent">*</span></label>
        <input id="name" name="name" type="text" required autocomplete="name" placeholder="Full name" value="<?php echo $old('name'); ?>">
        <?php if ($err('name') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($err('name'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="email">Email Address <span class="text-accent">*</span></label>
        <input id="email" name="email" type="email" required autocomplete="email" placeholder="you@example.com" value="<?php echo $old('email'); ?>">
        <?php if ($err('email') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($err('email'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="subject">Subject <span class="text-accent">*</span></label>
        <input id="subject" name="subject" type="text" required placeholder="Brief subject line" value="<?php echo $old('subject'); ?>">
        <?php if ($err('subject') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($err('subject'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="message">Message <span class="text-accent">*</span></label>
        <textarea id="message" name="message" required placeholder="Your message"><?php echo $old('message'); ?></textarea>
        <?php if ($err('message') !== ''): ?><p class="form-error"><?php echo htmlspecialchars($err('message'), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      </div>

      <p class="mt-lg">
        <button class="button button--lg" type="submit">Send Message</button>
      </p>
    </form>

  </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
