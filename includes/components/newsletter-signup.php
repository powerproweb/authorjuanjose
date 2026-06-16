<?php
declare(strict_types=1);

/**
 * Newsletter Signup Component
 *
 * Reusable embed for any page. Renders an inline signup form.
 *
 * Optional variables before include:
 *   $newsletter_heading  — custom heading (default: "Stay Connected")
 *   $newsletter_subtext  — custom description
 *   $newsletter_style    — 'inline' (horizontal) or 'stacked' (vertical, default)
 *   $newsletter_preselect — 'fiction', 'non-fiction', or '' (show selector)
 */

$newsletter_heading   = $newsletter_heading ?? 'Stay Connected';
$newsletter_subtext   = $newsletter_subtext ?? 'Get updates on new releases, journal entries, and exclusive content. Choose what interests you.';
$newsletter_style     = $newsletter_style ?? 'stacked';
$newsletter_preselect = $newsletter_preselect ?? '';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$nl_success = $_SESSION['newsletter_success'] ?? '';
$nl_error   = $_SESSION['newsletter_error'] ?? '';
unset($_SESSION['newsletter_success'], $_SESSION['newsletter_error']);

$nl_css = $newsletter_style === 'inline' ? 'newsletter-form--inline' : '';

require_once dirname(__DIR__) . '/db.php';
$newsletter_lists = [];
try {
    $newsletter_lists = get_active_mailing_lists(get_db());
} catch (Throwable $e) {
    $newsletter_lists = [];
}

$selected_list_slugs = ['author-news' => true];
if ($newsletter_preselect === 'fiction') {
    $selected_list_slugs['fiction'] = true;
} elseif ($newsletter_preselect === 'non-fiction') {
    $selected_list_slugs['non-fiction'] = true;
}
?>
<div class="newsletter-signup">
  <h3 class="newsletter-signup__heading"><?php echo htmlspecialchars($newsletter_heading, ENT_QUOTES, 'UTF-8'); ?></h3>
  <p class="newsletter-signup__text"><?php echo htmlspecialchars($newsletter_subtext, ENT_QUOTES, 'UTF-8'); ?></p>

  <?php if ($nl_success !== ''): ?>
    <div class="alert alert--success" role="status"><?php echo htmlspecialchars($nl_success, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <?php if ($nl_error !== ''): ?>
    <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($nl_error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <form method="post" action="/forms/submit-newsletter" class="newsletter-form <?php echo $nl_css; ?>" novalidate>
    <div class="hp-field" aria-hidden="true">
      <label for="nl_fax">Fax</label>
      <input id="nl_fax" name="fax" type="text" tabindex="-1" autocomplete="off">
    </div>

    <div class="newsletter-form__field">
      <input name="email" type="email" required placeholder="Your email address" autocomplete="email">
    </div>

    <div class="newsletter-form__field newsletter-form__field--name">
      <input name="name" type="text" placeholder="Name (optional)" autocomplete="name">
    </div>

    <div class="newsletter-form__field newsletter-form__field--lists">
      <fieldset class="newsletter-form__lists">
        <legend>Choose updates</legend>
        <?php if ($newsletter_lists !== []): ?>
          <?php foreach ($newsletter_lists as $list): ?>
            <?php
            $slug = strtolower((string)($list['slug'] ?? ''));
            $isChecked = isset($selected_list_slugs[$slug]);
            ?>
            <label class="newsletter-form__checkbox">
              <input type="checkbox" name="list_slugs[]" value="<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isChecked ? ' checked' : ''; ?>>
              <span>
                <strong><?php echo htmlspecialchars((string)($list['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php if (trim((string)($list['description'] ?? '')) !== ''): ?>
                  <small><?php echo htmlspecialchars((string)$list['description'], ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
              </span>
            </label>
          <?php endforeach; ?>
        <?php else: ?>
          <input type="hidden" name="list_slugs[]" value="author-news">
          <p class="newsletter-signup__text">You will receive Author News updates.</p>
        <?php endif; ?>
      </fieldset>
    </div>

    <div class="newsletter-form__field">
      <button class="button" type="submit">Subscribe</button>
    </div>
  </form>
</div>
