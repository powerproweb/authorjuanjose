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
$placeholder_count = (int)($placeholder_count ?? 8);
$placeholder_intro = trim((string)($placeholder_intro ?? ''));
$placeholder_items = $placeholder_items ?? [];
$placeholder_row_size = max(1, (int)($placeholder_row_size ?? 4));
$placeholder_pad_to_row = (bool)($placeholder_pad_to_row ?? true);
$coming_soon_cover = '/assets/images/book_covers/01_atticus_cvrs_6x9_800x1184_coming_soon.jpg';

$collection_lower = strtolower($placeholder_collection);
$items = [];

if (is_array($placeholder_items) && count($placeholder_items) > 0) {
    foreach (array_values($placeholder_items) as $index => $item) {
        if (!is_array($item)) {
            continue;
        }
        $title = trim((string)($item['title'] ?? 'Coming Soon'));
        $tagline = trim((string)($item['tagline'] ?? ($placeholder_collection . ' • Coming Soon')));
        $description = trim((string)($item['description'] ?? 'Description and purchase links are coming soon.'));
        $cover = trim((string)($item['cover'] ?? ''));
        $cover = $cover !== '' ? $cover : $coming_soon_cover;
        $items[] = [
            'title' => $title !== '' ? $title : 'Coming Soon',
            'tagline' => $tagline !== '' ? $tagline : ($placeholder_collection . ' • Coming Soon'),
            'description' => $description !== '' ? $description : 'Description and purchase links are coming soon.',
            'cover' => $cover,
            'cover_alt' => trim((string)($item['cover_alt'] ?? '')) ?: (($title !== '' ? $title : 'Coming Soon') . ' cover'),
            'is_coming_soon' => (bool)($item['is_coming_soon'] ?? false) || $cover === $coming_soon_cover,
        ];
    }
} elseif ($placeholder_count >= 1) {
    for ($i = 1; $i <= $placeholder_count; $i++) {
        $slot = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
        $items[] = [
            'title' => $placeholder_collection . ' Coming Soon ' . $slot,
            'tagline' => $placeholder_collection . ' • Coming Soon',
            'description' => 'This slot is reserved for an upcoming ' . $collection_lower . ' release.',
            'cover' => $coming_soon_cover,
            'cover_alt' => $placeholder_collection . ' coming soon cover ' . $slot,
            'is_coming_soon' => true,
        ];
    }
}

if (count($items) === 0) {
    return;
}

if ($placeholder_pad_to_row && $placeholder_row_size > 1) {
    $remainder = count($items) % $placeholder_row_size;
    if ($remainder !== 0) {
        $fill_count = $placeholder_row_size - $remainder;
        for ($i = 0; $i < $fill_count; $i++) {
            $items[] = [
                'title' => 'Coming Soon',
                'tagline' => $placeholder_collection . ' • Coming Soon',
                'description' => 'Description and purchase links are coming soon.',
                'cover' => $coming_soon_cover,
                'cover_alt' => $placeholder_collection . ' coming soon cover',
                'is_coming_soon' => true,
            ];
        }
    }
}
?>
<?php if ($placeholder_intro !== ''): ?>
  <p class="lead"><?php echo htmlspecialchars($placeholder_intro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>
<section class="book-list" aria-label="<?php echo htmlspecialchars($placeholder_collection, ENT_QUOTES, 'UTF-8'); ?> placeholders">
  <?php foreach ($items as $item): ?>
    <article class="book-card<?php echo $item['is_coming_soon'] ? ' book-card--coming-soon' : ''; ?>">
      <figure>
        <div class="book-placeholder-cover<?php echo $item['is_coming_soon'] ? ' is-coming-soon' : ''; ?>">
          <img class="book-placeholder-cover__image"
               src="<?php echo htmlspecialchars($item['cover'], ENT_QUOTES, 'UTF-8'); ?>"
               alt="<?php echo htmlspecialchars($item['cover_alt'], ENT_QUOTES, 'UTF-8'); ?>"
               loading="lazy">
          <?php if ($item['is_coming_soon']): ?>
            <span class="book-placeholder-cover__label">Coming Soon</span>
          <?php endif; ?>
        </div>
      </figure>
      <div class="book-meta">
        <div class="tagline"><?php echo htmlspecialchars($item['tagline'], ENT_QUOTES, 'UTF-8'); ?></div>
        <h3><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
        <p class="desc"><?php echo htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8'); ?></p>
        <nav class="kdp-links" aria-label="Placeholder formats">
          <span class="buy-btn buy-btn--static">Details Coming Soon</span>
          <span class="buy-btn is-disabled x-out">Audiobook</span>
          <span class="buy-btn is-disabled x-out">eBook</span>
          <span class="buy-btn is-disabled x-out">Paperback</span>
          <span class="buy-btn is-disabled x-out">Hardcover</span>
          <span class="buy-btn is-disabled x-out">Books.io</span>
        </nav>
      </div>
    </article>
  <?php endforeach; ?>
</section>
