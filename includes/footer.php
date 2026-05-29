<?php
declare(strict_types=1);

require_once __DIR__ . '/site-config.php';
?>
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-grid__newsletter">
          <?php
          $newsletter_heading = 'Newsletter';
          $newsletter_subtext = 'Get notified about new releases and updates.';
          $newsletter_style = 'inline';
          include __DIR__ . '/components/newsletter-signup.php';
          ?>
        </div>
        <div class="footer-grid__copy">
          <p>&copy; <?php echo (int)$site['year']; ?> <?php echo htmlspecialchars($site['name'], ENT_QUOTES, 'UTF-8'); ?>. All rights reserved.</p>
          <p><a href="/privacy">Privacy Policy</a></p>
          <?php if (isset($_SESSION['site_auth']) && $_SESSION['site_auth'] === true): ?>
            <p class="footer-grid__utility-link"><a href="/?site_logout=1">Site Logout</a></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </footer>
  <script src="/assets/js/main.js?v=6" defer></script>
</body>
</html>
