<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/member-auth.php';

// Flash messages
$upload_success = $_SESSION['gallery_success'] ?? '';
$upload_error   = $_SESSION['gallery_error'] ?? '';
unset($_SESSION['gallery_success'], $_SESSION['gallery_error']);

// My uploads
$stmt = $pdo->prepare('SELECT * FROM gallery_uploads WHERE member_id = ? ORDER BY uploaded_at DESC');
$stmt->execute([$arc_member['id']]);
$my_uploads = $stmt->fetchAll();

$page_title = 'Upload Artwork | Gallery';
$show_arc_sub_navigation = true;
$show_member_navigation = true;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">
  <div class="container--narrow" style="margin:0 auto">

    <h1>Upload Your Artwork</h1>
    <p class="lead">Share a photo of your completed coloring book page. Uploads are reviewed before appearing in the public gallery.</p>

    <hr class="ornament-rule">

    <?php if ($upload_success !== ''): ?>
      <div class="alert alert--success" role="status"><?php echo htmlspecialchars($upload_success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($upload_error !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($upload_error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="/forms/submit-gallery-upload" enctype="multipart/form-data" novalidate>

      <div class="form-group">
        <label for="image">Photo <span class="text-accent">*</span></label>
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" required>
        <small>JPEG, PNG, or WebP. Maximum 5MB.</small>
      </div>

      <div class="form-group">
        <label for="caption">Caption (optional)</label>
        <input id="caption" name="caption" type="text" placeholder="Describe your artwork" maxlength="200">
      </div>

      <p class="mt-lg">
        <button class="button button--lg" type="submit">Upload</button>
      </p>

    </form>
  </div>

  <!-- My Uploads -->
  <?php if (count($my_uploads) > 0): ?>
    <div class="divider-gear"></div>
    <section class="section">
      <h2>My Uploads</h2>
      <div class="gallery-grid">
        <?php foreach ($my_uploads as $u): ?>
          <div class="gallery-item">
            <img src="<?php echo htmlspecialchars($u['thumbnail_path'] !== '' ? $u['thumbnail_path'] : $u['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                 alt="Your upload" loading="lazy">
            <div class="gallery-item__info">
              <span class="campaign-status campaign-status--<?php echo $u['status'] === 'approved' ? 'completed' : ($u['status'] === 'rejected' ? 'declined' : 'invited'); ?>">
                <?php echo ucfirst(htmlspecialchars($u['status'], ENT_QUOTES, 'UTF-8')); ?>
              </span>
              <?php if ($u['caption'] !== ''): ?>
                <p><?php echo htmlspecialchars($u['caption'], ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
