<?php
declare(strict_types=1);

/**
 * Buy Links Component
 *
 * Renders a row of purchase buttons for a book.
 * Expects $buy_links to be set before including this file:
 *   $buy_links = [['platform' => 'Amazon', 'url' => '...'], ...]
 *
 * Optional: $buy_links_label (string) — heading text above the links.
 */

$buy_links       = $buy_links ?? [];
$buy_links_label = $buy_links_label ?? 'Get Your Copy';

if (count($buy_links) === 0) {
    return;
}
?>
<div class="buy-links">
  <h3 class="buy-links__heading"><?php echo htmlspecialchars($buy_links_label, ENT_QUOTES, 'UTF-8'); ?></h3>
  <div class="buy-links__row">
    <?php foreach ($buy_links as $link): ?>
      <a class="button button--outline buy-links__btn"
         href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>"
         target="_blank"
         rel="noopener noreferrer">
        <?php echo htmlspecialchars($link['platform'], ENT_QUOTES, 'UTF-8'); ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>
