<?php
declare(strict_types=1);

$page_title = 'Accessibility Statement | AuthorJuanJose.io';
$page_description = 'Accessibility commitments and contact pathway for AuthorJuanJose.io.';
require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="container page-shell">
  <section class="section">
    <p class="section-label">Accessibility</p>
    <h1>Accessibility Statement</h1>
    <p>AuthorJuanJose.io is designed to be usable by as many readers as possible, including readers using assistive technologies.</p>
    <p>Current baseline targets include keyboard-accessible navigation, visible focus indicators, semantic landmarks, reduced-motion support, and readable color contrast for primary content.</p>
  </section>

  <section class="section">
    <p class="section-label">Scope</p>
    <h2>What We Are Maintaining</h2>
    <ul class="benefit-list">
      <li>Consistent heading hierarchy and one primary page heading per page</li>
      <li>Keyboard operability for menus, forms, and interactive components</li>
      <li>Form labels and error visibility for required fields</li>
      <li>Reduced-motion fallback behavior for users who request it</li>
      <li>Ongoing review for contrast and readability issues</li>
    </ul>
  </section>

  <section class="section">
    <p class="section-label">Report an Issue</p>
    <h2>Need Help Accessing Content?</h2>
    <p>If you encounter an accessibility barrier, send a message through the contact page with the URL and issue details so it can be corrected quickly.</p>
    <p><a class="button" href="/contact">Contact the Author Team</a></p>
  </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
