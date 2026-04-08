<?php
declare(strict_types=1);

/**
 * Related Books Component
 *
 * Renders a card grid of related titles.
 * Expects before include:
 *   $related_books — array of book data arrays
 *   $related_type  — 'fiction' or 'non-fiction' (for URL prefix)
 *
 * Optional:
 *   $related_heading — custom section heading
 */

$related_books   = $related_books ?? [];
$related_type    = $related_type ?? 'fiction';
$related_heading = $related_heading ?? 'You May Also Enjoy';

if (count($related_books) === 0) {
    return;
}

$url_prefix = $related_type === 'fiction' ? '/fiction' : '/non-fiction';
?>
<section class="section related-books">
  <p class="section-label">More to Explore</p>
  <h2><?php echo htmlspecialchars($related_heading, ENT_QUOTES, 'UTF-8'); ?></h2>
  <div class="card-grid">
    <?php foreach ($related_books as $rel): ?>
      <a class="book-card" href="<?php echo htmlspecialchars($url_prefix . '/' . $rel['slug'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (!empty($rel['cover'])): ?>
          <div class="book-card__cover">
            <img src="<?php echo htmlspecialchars($rel['cover'], ENT_QUOTES, 'UTF-8'); ?>"
                 alt="<?php echo htmlspecialchars($rel['short_title'] ?? $rel['title'], ENT_QUOTES, 'UTF-8'); ?> cover"
                 loading="lazy">
          </div>
        <?php endif; ?>
        <div class="book-card__info">
          <h3><?php echo htmlspecialchars($rel['short_title'] ?? $rel['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <p><?php echo htmlspecialchars($rel['hook'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
