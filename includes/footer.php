<?php
declare(strict_types=1);

require_once __DIR__ . '/site-config.php';
?>
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-grid__brand">
          <p class="footer-wordmark"><?php echo htmlspecialchars($site['name'], ENT_QUOTES, 'UTF-8'); ?></p>
          <p class="footer-tagline"><?php echo htmlspecialchars((string)$site['tagline'], ENT_QUOTES, 'UTF-8'); ?></p>
          <p class="footer-social-note">Official author site and reader gateway.</p>
        </div>
        <nav class="footer-grid__sitemap" aria-label="Footer sitemap">
          <p class="footer-col-title">Explore</p>
          <ul>
            <li><a href="/fiction">Books</a></li>
            <li><a href="/journal">Updates</a></li>
            <li><a href="/about">About</a></li>
            <li><a href="/start-here">Start Here</a></li>
            <li><a href="/arc-reader-club">ARC Reader Club</a></li>
          </ul>
        </nav>
        <div class="footer-grid__newsletter">
          <?php
          $newsletter_heading = 'Signal Wire';
          $newsletter_subtext = 'Get launch intel, early access updates, and new release notices.';
          $newsletter_style = 'stacked';
          include __DIR__ . '/components/newsletter-signup.php';
          ?>
        </div>
      </div>
      <div class="footer-meta">
        <p>&copy; <?php echo (int)$site['year']; ?> <?php echo htmlspecialchars($site['name'], ENT_QUOTES, 'UTF-8'); ?>. All rights reserved.</p>
        <p class="footer-grid__policy-links">
          <a href="/privacy">Privacy Policy</a>
          <span aria-hidden="true">|</span>
          <a href="/terms">Terms of Service</a>
          <span aria-hidden="true">|</span>
          <a href="/accessibility">Accessibility</a>
          <span aria-hidden="true">|</span>
          <a href="/contact">Contact</a>
        </p>
        <?php if (isset($_SESSION['site_auth']) && $_SESSION['site_auth'] === true): ?>
          <p class="footer-grid__utility-link"><a href="/?site_logout=1">Site Logout</a></p>
        <?php endif; ?>
      </div>
    </div>
    <div class="backtotop" aria-hidden="true"><a href="#top">Back to top</a></div>
  </footer>
  <script src="/assets/js/main.js?v=epic-01" defer></script>
</body>
</html>
