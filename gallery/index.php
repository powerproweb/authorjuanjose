<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = get_db();

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

$sort = (string)($_GET['sort'] ?? 'recent');
$order_by = $sort === 'oldest' ? 'g.uploaded_at ASC' : 'g.uploaded_at DESC';

$total = (int)$pdo->query('SELECT COUNT(*) FROM gallery_uploads WHERE status = "approved"')->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

$stmt = $pdo->prepare("
    SELECT g.*, m.name AS member_name
    FROM gallery_uploads g
    JOIN members m ON m.id = g.member_id
    WHERE g.status = 'approved'
    ORDER BY {$order_by}
    LIMIT ? OFFSET ?
");
$stmt->execute([$per_page, $offset]);
$images = $stmt->fetchAll();

$page_title = 'Coloring Book Gallery | AuthorJuanJose.io';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Coloring Book Gallery</h1>
  <p class="lead">Artwork from our readers. Members can upload photos of their completed coloring book pages.</p>

  <hr class="ornament-rule">

  <!-- Sort + Upload CTA -->
  <div class="gallery-toolbar">
    <div class="admin-filters">
      <a class="button <?php echo $sort === 'recent' ? '' : 'button--outline'; ?>" href="/gallery?sort=recent">Newest</a>
      <a class="button <?php echo $sort === 'oldest' ? '' : 'button--outline'; ?>" href="/gallery?sort=oldest">Oldest</a>
    </div>
    <a class="button" href="/gallery/upload">Upload Your Artwork</a>
  </div>

  <!-- Gallery Grid -->
  <?php if (count($images) > 0): ?>
    <div class="gallery-grid">
      <?php foreach ($images as $img): ?>
        <div class="gallery-item" data-full="<?php echo htmlspecialchars($img['image_path'], ENT_QUOTES, 'UTF-8'); ?>">
          <img src="<?php echo htmlspecialchars($img['thumbnail_path'] !== '' ? $img['thumbnail_path'] : $img['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
               alt="Coloring page by <?php echo htmlspecialchars($img['member_name'], ENT_QUOTES, 'UTF-8'); ?>"
               loading="lazy">
          <div class="gallery-item__info">
            <strong><?php echo htmlspecialchars($img['member_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php if ($img['caption'] !== ''): ?>
              <p><?php echo htmlspecialchars($img['caption'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <div class="gallery-pagination">
        <?php if ($page > 1): ?>
          <a class="button button--outline" href="/gallery?page=<?php echo $page - 1; ?>&sort=<?php echo $sort; ?>">&larr; Previous</a>
        <?php endif; ?>
        <span class="gallery-pagination__info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
        <?php if ($page < $total_pages): ?>
          <a class="button button--outline" href="/gallery?page=<?php echo $page + 1; ?>&sort=<?php echo $sort; ?>">Next &rarr;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <div class="panel">
      <p>No artwork has been shared yet. Be the first to upload your coloring book page!</p>
      <p><a class="button" href="/gallery/upload">Upload Now</a></p>
    </div>
  <?php endif; ?>

  <!-- Lightbox (JS-driven) -->
  <div class="lightbox" id="lightbox" hidden>
    <button class="lightbox__close" type="button" aria-label="Close">&times;</button>
    <img class="lightbox__img" id="lightbox-img" src="" alt="">
  </div>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
