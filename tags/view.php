<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/book-catalog.php';

$tag = trim((string)($_GET['tag'] ?? ''));

if ($tag === '') {
    header('Location: /tags');
    exit;
}

$books = get_books_by_tag($tag);

$page_title = ucfirst($tag) . ' Books | AuthorJuanJose.io';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <p class="section-label"><a href="/tags">&larr; All Tags</a></p>
  <h1>Tag: <?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></h1>
  <p class="lead"><?php echo count($books); ?> book<?php echo count($books) !== 1 ? 's' : ''; ?> tagged &ldquo;<?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>&rdquo;</p>

  <hr class="ornament-rule">

  <?php if (count($books) > 0): ?>
    <div class="card-grid">
      <?php foreach ($books as $book): ?>
        <?php $url_prefix = ($book['_type'] ?? 'fiction') === 'fiction' ? '/fiction' : '/non-fiction'; ?>
        <a class="book-card" href="<?php echo $url_prefix . '/' . htmlspecialchars($book['slug'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php if (!empty($book['cover'])): ?>
            <div class="book-card__cover">
              <img src="<?php echo htmlspecialchars($book['cover'], ENT_QUOTES, 'UTF-8'); ?>"
                   alt="<?php echo htmlspecialchars($book['short_title'] ?? $book['title'], ENT_QUOTES, 'UTF-8'); ?>"
                   loading="lazy">
            </div>
          <?php endif; ?>
          <div class="book-card__info">
            <span class="book-badge book-badge--<?php echo ($book['_type'] ?? 'fiction') === 'fiction' ? 'fiction' : 'nonfiction'; ?>">
              <?php echo ucfirst($book['_type'] ?? 'fiction'); ?>
            </span>
            <h3><?php echo htmlspecialchars($book['short_title'] ?? $book['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p><?php echo htmlspecialchars($book['hook'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No books found with this tag.</p>
  <?php endif; ?>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
