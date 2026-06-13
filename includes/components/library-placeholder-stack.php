<?php
declare(strict_types=1);

/**
 * Library Placeholder Stack Component
 *
 * Dedicated component for /library so its placeholder layout remains stable
 * even when shared catalog components change.
 *
 * Expected before include:
 *   $library_placeholder_collection (string)
 *
 * Optional:
 *   $library_placeholder_count (int) defaults to 8
 *   $library_placeholder_intro (string) optional lead copy shown above the list
 */

$library_placeholder_collection = trim((string)($library_placeholder_collection ?? 'Library'));
$library_placeholder_count = (int)($library_placeholder_count ?? 8);
$library_placeholder_intro = trim((string)($library_placeholder_intro ?? ''));

if ($library_placeholder_count < 1) {
    return;
}

$collection_lower = strtolower($library_placeholder_collection);
?>
<?php if ($library_placeholder_intro !== ''): ?>
  <p class="lead"><?php echo htmlspecialchars($library_placeholder_intro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>
<section class="library-placeholder-list" aria-label="<?php echo htmlspecialchars($library_placeholder_collection, ENT_QUOTES, 'UTF-8'); ?> placeholders">
  <?php for ($i = 1; $i <= $library_placeholder_count; $i++): ?>
    <?php $slot = str_pad((string)$i, 2, '0', STR_PAD_LEFT); ?>
    <article class="library-placeholder-card">
      <figure class="library-placeholder-card__figure">
        <div class="library-placeholder-cover" role="img" aria-label="<?php echo htmlspecialchars($library_placeholder_collection . ' placeholder cover ' . $slot, ENT_QUOTES, 'UTF-8'); ?>">
          <span class="library-placeholder-cover__label"><?php echo htmlspecialchars($library_placeholder_collection, ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="library-placeholder-cover__number">Slot <?php echo htmlspecialchars($slot, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </figure>
      <div class="library-placeholder-meta">
        <div class="library-placeholder-meta__tagline"><?php echo htmlspecialchars($library_placeholder_collection, ENT_QUOTES, 'UTF-8'); ?> • Placeholder</div>
        <h3><?php echo htmlspecialchars($library_placeholder_collection, ENT_QUOTES, 'UTF-8'); ?> Placeholder Title <?php echo htmlspecialchars($slot, ENT_QUOTES, 'UTF-8'); ?></h3>
        <p>This placeholder slot is reserved for an upcoming <?php echo htmlspecialchars($collection_lower, ENT_QUOTES, 'UTF-8'); ?> release.</p>
        <nav class="library-placeholder-links" aria-label="Placeholder formats">
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
