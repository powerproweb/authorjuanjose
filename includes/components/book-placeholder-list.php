<?php
declare(strict_types=1);

/**
 * Placeholder Book List Component
 *
 * Expected before include:
 *   $placeholder_collection (string)
 *
 * Optional:
 *   $placeholder_count (int) defaults to 8
 *   $placeholder_intro (string) optional lead copy shown above the list
 */

$placeholder_collection = trim((string)($placeholder_collection ?? 'Library'));
$placeholder_count      = (int)($placeholder_count ?? 8);
$placeholder_intro      = trim((string)($placeholder_intro ?? ''));

if ($placeholder_count < 1) {
    return;
}

$collection_lower = strtolower($placeholder_collection);
?>
<?php if ($placeholder_intro !== ''): ?>
  <p class="lead"><?php echo htmlspecialchars($placeholder_intro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>
<section class="book-list" aria-label="<?php echo htmlspecialchars($placeholder_collection, ENT_QUOTES, 'UTF-8'); ?> placeholders">
  <?php for ($i = 1; $i <= $placeholder_count; $i++): ?>
    <?php $slot = str_pad((string)$i, 2, '0', STR_PAD_LEFT); ?>
    <article class="book-card">
      <figure>
        <div class="book-placeholder-cover" role="img" aria-label="<?php echo htmlspecialchars($placeholder_collection . ' placeholder cover ' . $slot, ENT_QUOTES, 'UTF-8'); ?>">
          <span class="book-placeholder-cover__label"><?php echo htmlspecialchars($placeholder_collection, ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="book-placeholder-cover__number">Slot <?php echo htmlspecialchars($slot, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </figure>
      <div class="book-meta">
        <div class="tagline"><?php echo htmlspecialchars($placeholder_collection, ENT_QUOTES, 'UTF-8'); ?> • Placeholder</div>
        <h3><?php echo htmlspecialchars($placeholder_collection, ENT_QUOTES, 'UTF-8'); ?> Placeholder Title <?php echo htmlspecialchars($slot, ENT_QUOTES, 'UTF-8'); ?></h3>
        <p class="desc">This placeholder slot is reserved for an upcoming <?php echo htmlspecialchars($collection_lower, ENT_QUOTES, 'UTF-8'); ?> release.</p>
        <nav class="kdp-links" aria-label="Placeholder formats">
          <span class="buy-btn buy-btn--static">Description/Purchase</span>
          <span class="buy-btn is-disabled x-out">Audiobook</span>
          <span class="buy-btn is-disabled x-out">eBook</span>
          <span class="buy-btn is-disabled x-out">Paperback</span>
          <span class="buy-btn is-disabled x-out">Hardcover</span>
          <span class="buy-btn is-disabled x-out">Books.io</span>
        </nav>
      </div>
    </article>
  <?php endfor; ?>
</section>
