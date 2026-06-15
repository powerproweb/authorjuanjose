<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/journal-data.php';
require_once __DIR__ . '/includes/admin-auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$journal_editor_anchor = '/journal#journal-editor';

if (isset($_GET['author_logout'])) {
    unset($_SESSION['admin_auth'], $_SESSION['admin_auth_user']);
    header('Location: /journal', true, 303);
    exit;
}

if (isset($_GET['author_login'])) {
    ajj_require_admin_auth('Journal Editor');
    header('Location: ' . $journal_editor_anchor, true, 303);
    exit;
}

$is_author_logged_in = isset($_SESSION['admin_auth_user'])
    && is_string($_SESSION['admin_auth_user'])
    && hash_equals(ajj_admin_auth_expected_user(), $_SESSION['admin_auth_user']);

$form_values = [
    'title' => '',
    'category' => 'Behind the Scenes',
    'excerpt' => '',
    'body' => '',
];
$form_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_journal_create'])) {
    ajj_require_admin_auth('Journal Editor');
    $is_author_logged_in = true;

    $form_values['title'] = trim((string)($_POST['journal_title'] ?? ''));
    $form_values['category'] = trim((string)($_POST['journal_category'] ?? 'Behind the Scenes'));
    $form_values['excerpt'] = trim((string)($_POST['journal_excerpt'] ?? ''));
    $form_values['body'] = trim((string)($_POST['journal_body'] ?? ''));

    if ($form_values['title'] === '') {
        $form_errors[] = 'Title is required.';
    }
    if ($form_values['body'] === '') {
        $form_errors[] = 'Entry body is required.';
    }
    if (!in_array($form_values['category'], ajj_journal_categories(), true)) {
        $form_values['category'] = 'Behind the Scenes';
    }

    if ($form_errors === []) {
        $journal_entries_to_save = ajj_load_journal_entries();
        $existing_slugs = array_map(
            static fn(array $entry): string => (string)($entry['slug'] ?? ''),
            $journal_entries_to_save
        );

        $new_entry = [
            'title' => $form_values['title'],
            'date' => date('Y-m-d'),
            'category' => $form_values['category'],
            'excerpt' => $form_values['excerpt'] !== '' ? $form_values['excerpt'] : ajj_journal_make_excerpt($form_values['body']),
            'body' => $form_values['body'],
            'slug' => ajj_journal_generate_unique_slug($form_values['title'], $existing_slugs),
        ];

        array_unshift($journal_entries_to_save, $new_entry);

        if (ajj_save_journal_entries($journal_entries_to_save)) {
            $_SESSION['journal_flash_success'] = 'Journal entry published.';
            header('Location: ' . $journal_editor_anchor, true, 303);
            exit;
        }

        $form_errors[] = 'Could not save the journal entry. Please try again.';
    }
}

$flash_success = '';
if (isset($_SESSION['journal_flash_success']) && is_string($_SESSION['journal_flash_success'])) {
    $flash_success = $_SESSION['journal_flash_success'];
    unset($_SESSION['journal_flash_success']);
}

$journal_entries = ajj_load_journal_entries();
$categories = array_merge(['All'], ajj_journal_categories());
foreach ($journal_entries as $entry) {
    if (isset($entry['category']) && is_string($entry['category']) && !in_array($entry['category'], $categories, true)) {
        $categories[] = $entry['category'];
    }
}

$active_category = trim((string)($_GET['category'] ?? 'All'));
if (!in_array($active_category, $categories, true)) {
    $active_category = 'All';
}

$visible_entries = $journal_entries;
if ($active_category !== 'All') {
    $visible_entries = array_values(array_filter(
        $journal_entries,
        static fn(array $entry): bool => ($entry['category'] ?? '') === $active_category
    ));
}

$page_title = 'Journal | AuthorJuanJose.io';
$page_description = 'Writing updates, behind-the-book notes, essays, and reflections from Author Juan Jose.';
require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="container page-shell">

  <h1>Journal</h1>
  <p class="lead">Writing updates, behind-the-book notes, essays, reflections, and the thinking behind the work.</p>

  <hr class="ornament-rule">

  <!-- Category Filters -->
  <div class="admin-filters">
    <?php foreach ($categories as $cat): ?>
      <?php
      $is_active_filter = $cat === $active_category;
      $filter_url = $cat === 'All'
          ? '/journal'
          : '/journal?category=' . rawurlencode($cat);
      ?>
      <a
        class="<?php echo $is_active_filter ? 'button' : 'button button--outline'; ?>"
        href="<?php echo htmlspecialchars($filter_url, ENT_QUOTES, 'UTF-8'); ?>"
        <?php echo $is_active_filter ? 'aria-current="true"' : ''; ?>
      ><?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?></a>
    <?php endforeach; ?>
  </div>
  <section id="journal-editor" class="panel mb-lg">
    <h2 class="mt-0">Journal Editor</h2>
    <?php if ($is_author_logged_in): ?>
      <p>Publish new journal entries directly from this page.</p>

      <?php if ($flash_success !== ''): ?>
        <div class="alert alert--success"><?php echo htmlspecialchars($flash_success, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <?php if ($form_errors !== []): ?>
        <div class="alert alert--error">
          <ul class="mb-0">
            <?php foreach ($form_errors as $error): ?>
              <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" action="/journal#journal-editor">
        <input type="hidden" name="_journal_create" value="1">

        <div class="form-group">
          <label for="journal_title">Title</label>
          <input
            id="journal_title"
            name="journal_title"
            type="text"
            required
            maxlength="180"
            value="<?php echo htmlspecialchars($form_values['title'], ENT_QUOTES, 'UTF-8'); ?>"
          >
        </div>

        <div class="form-group">
          <label for="journal_category">Category</label>
          <select id="journal_category" name="journal_category" required>
            <?php foreach (ajj_journal_categories() as $editor_category): ?>
              <option value="<?php echo htmlspecialchars($editor_category, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $form_values['category'] === $editor_category ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($editor_category, ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="journal_excerpt">Excerpt (optional)</label>
          <textarea id="journal_excerpt" name="journal_excerpt" rows="3" maxlength="320"><?php echo htmlspecialchars($form_values['excerpt'], ENT_QUOTES, 'UTF-8'); ?></textarea>
          <small>If left empty, an excerpt is generated automatically.</small>
        </div>

        <div class="form-group">
          <label for="journal_body">Entry Body</label>
          <textarea id="journal_body" name="journal_body" rows="10" required><?php echo htmlspecialchars($form_values['body'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <button class="button" type="submit">Publish Entry</button>
        <a class="button button--outline" href="/journal?author_logout=1">Sign Out Editor</a>
      </form>
    <?php else: ?>
      <p>Author sign-in is required to publish from this page.</p>
      <a class="button" href="/journal?author_login=1#journal-editor">Sign In to Journal Editor</a>
    <?php endif; ?>
  </section>

  <!-- Entries -->
  <?php if (count($visible_entries) > 0): ?>
    <div class="journal-list">
      <?php foreach ($visible_entries as $entry): ?>
        <article id="<?php echo htmlspecialchars($entry['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="journal-entry">
          <div class="journal-entry__meta">
            <span class="journal-entry__date"><?php echo htmlspecialchars($entry['date'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="tag"><?php echo htmlspecialchars($entry['category'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <h2 class="journal-entry__title"><?php echo htmlspecialchars($entry['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
          <?php if ($entry['excerpt'] !== ''): ?>
            <p><?php echo htmlspecialchars($entry['excerpt'], ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>
          <div class="book-prose">
            <?php echo nl2br(htmlspecialchars($entry['body'], ENT_QUOTES, 'UTF-8')); ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="panel">
      <p>No journal entries are available in this category yet.</p>
    </div>
  <?php endif; ?>

  <div class="divider-gear"></div>

  <!-- CTA -->
  <section class="section text-center">
    <h2>Never Miss an Update</h2>
    <p class="lead" style="margin:0 auto var(--space-lg)">Join the ARC Reader Club for early access to new writing and exclusive behind-the-scenes content.</p>
    <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
  </section>

</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
