<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-gate.php';
require_once __DIR__ . '/site-config.php';

$page_title = $page_title ?? $site['name'];
$show_arc_sub_navigation = $show_arc_sub_navigation ?? false;
$show_member_navigation = $show_member_navigation ?? false;

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$current_path = parse_url($request_uri, PHP_URL_PATH);
$current_path = is_string($current_path) && $current_path !== '' ? $current_path : '/';

$normalize_path = static function (string $path): string {
    $trimmed = rtrim($path, '/');
    return $trimmed === '' ? '/' : $trimmed;
};

$is_active = static function (string $target_path) use ($normalize_path, $current_path): bool {
    return $normalize_path($target_path) === $normalize_path($current_path);
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Official website of Author Juan Jose and the ARC Reader Club.">
  <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
  <header class="site-header">
    <div class="container">
      <a class="site-brand" href="/"><?php echo htmlspecialchars($site['name'], ENT_QUOTES, 'UTF-8'); ?></a>
      <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">&#9776;</button>
      <nav class="main-nav" aria-label="Main site navigation">
        <ul class="nav-list">
          <?php foreach ($main_navigation as $item): ?>
            <li>
              <a class="<?php echo $is_active($item['url']) ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
            </li>
          <?php endforeach; ?>
          <?php if (isset($_SESSION['site_auth']) && $_SESSION['site_auth'] === true): ?>
            <li>
              <a href="/?site_logout=1">Site Logout</a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </header>

  <?php if ($show_arc_sub_navigation): ?>
    <nav class="subnav" aria-label="ARC Reader Club navigation">
      <div class="container">
        <ul class="nav-list">
          <?php foreach ($arc_navigation as $item): ?>
            <li>
              <a class="<?php echo $is_active($item['url']) ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </nav>
  <?php endif; ?>

  <?php if ($show_member_navigation): ?>
    <nav class="subnav subnav-member" aria-label="Logged-in member navigation">
      <div class="container">
        <ul class="nav-list">
          <?php foreach ($member_navigation as $item): ?>
            <li>
              <a class="<?php echo $is_active($item['url']) ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </nav>
  <?php endif; ?>
