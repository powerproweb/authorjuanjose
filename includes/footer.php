<?php
declare(strict_types=1);

require_once __DIR__ . '/site-config.php';
?>
  <footer class="site-footer">
    <div class="container">
      <p>&copy; <?php echo (int)$site['year']; ?> <?php echo htmlspecialchars($site['name'], ENT_QUOTES, 'UTF-8'); ?>. All rights reserved.</p>
    </div>
  </footer>
  <script src="/assets/js/main.js" defer></script>
</body>
</html>
