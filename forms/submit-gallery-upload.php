<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/member-auth.php';

$redirect = static function (string $url): void {
    header('Location: ' . $url, true, 303);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$caption = trim((string)($_POST['caption'] ?? ''));
if (mb_strlen($caption) > 200) {
    $caption = mb_substr($caption, 0, 200);
}

// Validate file
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['gallery_error'] = 'Please select an image to upload.';
    $redirect('/gallery/upload');
}

$file = $_FILES['image'];
$max_size = 5 * 1024 * 1024; // 5MB

if ($file['size'] > $max_size) {
    $_SESSION['gallery_error'] = 'Image is too large. Maximum size is 5MB.';
    $redirect('/gallery/upload');
}

$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed_types, true)) {
    $_SESSION['gallery_error'] = 'Invalid file type. Please upload a JPEG, PNG, or WebP image.';
    $redirect('/gallery/upload');
}

// Generate unique filename
$ext = match($mime) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    default      => 'jpg',
};
$uuid = bin2hex(random_bytes(16));
$filename      = $uuid . '.' . $ext;
$thumb_filename = $uuid . '_thumb.' . $ext;

$gallery_dir = dirname(__DIR__) . '/assets/images/gallery';
if (!is_dir($gallery_dir)) {
    mkdir($gallery_dir, 0755, true);
}

$dest_path  = $gallery_dir . '/' . $filename;
$thumb_path = $gallery_dir . '/' . $thumb_filename;

// Load image with GD
$src_image = match($mime) {
    'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
    'image/png'  => imagecreatefrompng($file['tmp_name']),
    'image/webp' => imagecreatefromwebp($file['tmp_name']),
    default      => false,
};

if ($src_image === false) {
    $_SESSION['gallery_error'] = 'Could not process the image. Please try a different file.';
    $redirect('/gallery/upload');
}

$orig_w = imagesx($src_image);
$orig_h = imagesy($src_image);

// Resize main image to max 1200px wide
$max_w = 1200;
if ($orig_w > $max_w) {
    $new_w = $max_w;
    $new_h = (int)round($orig_h * ($max_w / $orig_w));
    $resized = imagecreatetruecolor($new_w, $new_h);
    imagecopyresampled($resized, $src_image, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);
} else {
    $resized = $src_image;
    $new_w = $orig_w;
    $new_h = $orig_h;
}

// Save main image
match($ext) {
    'jpg'  => imagejpeg($resized, $dest_path, 85),
    'png'  => imagepng($resized, $dest_path, 6),
    'webp' => imagewebp($resized, $dest_path, 85),
};

// Generate thumbnail (400px wide)
$thumb_max = 400;
$thumb_w = min($thumb_max, $new_w);
$thumb_h = (int)round($new_h * ($thumb_w / $new_w));
$thumbnail = imagecreatetruecolor($thumb_w, $thumb_h);
imagecopyresampled($thumbnail, $resized, 0, 0, 0, 0, $thumb_w, $thumb_h, $new_w, $new_h);

match($ext) {
    'jpg'  => imagejpeg($thumbnail, $thumb_path, 80),
    'png'  => imagepng($thumbnail, $thumb_path, 7),
    'webp' => imagewebp($thumbnail, $thumb_path, 80),
};

imagedestroy($src_image);
if ($resized !== $src_image) imagedestroy($resized);
imagedestroy($thumbnail);

// Save to database
$image_url = '/assets/images/gallery/' . $filename;
$thumb_url = '/assets/images/gallery/' . $thumb_filename;

$stmt = $pdo->prepare('INSERT INTO gallery_uploads (member_id, image_path, thumbnail_path, caption) VALUES (?, ?, ?, ?)');
$stmt->execute([$arc_member['id'], $image_url, $thumb_url, $caption]);

$_SESSION['gallery_success'] = 'Your artwork has been uploaded and is pending review. It will appear in the gallery once approved.';
$redirect('/gallery/upload');
