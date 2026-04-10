<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/book-catalog.php';

$step = (string)($_GET['step'] ?? '');
$interest = (string)($_GET['interest'] ?? '');
$theme = (string)($_GET['theme'] ?? '');

$recommendations = [];
$show_results = false;

// Step 3: Generate recommendations
if ($step === 'results' && $interest !== '') {
    $show_results = true;
    $all_books = get_all_books();

    // Score each book based on answers
    $scored = [];
    foreach ($all_books as $book) {
        $score = 0;

        // Match type preference
        if ($interest === 'fiction' && $book['_type'] === 'fiction') $score += 3;
        if ($interest === 'non-fiction' && $book['_type'] === 'non-fiction') $score += 3;
        if ($interest === 'both') $score += 1;

        // Match theme preference
        if ($theme !== '') {
            $book_tags = array_map('strtolower', $book['tags'] ?? []);
            if (in_array(strtolower($theme), $book_tags, true)) {
                $score += 5;
            }
            // Partial matches
            foreach ($book_tags as $tag) {
                if (str_contains($tag, strtolower($theme)) || str_contains(strtolower($theme), $tag)) {
                    $score += 2;
                }
            }
        }

        // Bonus for published books
        if ($book['status'] === 'published') $score += 1;

        if ($score > 0) {
            $book['_score'] = $score;
            $scored[] = $book;
        }
    }

    usort($scored, fn($a, $b) => $b['_score'] <=> $a['_score']);
    $recommendations = array_slice($scored, 0, 3);

    // Fallback: if no strong matches, show first from each type
    if (count($recommendations) === 0) {
        $fiction = get_fiction_books();
        $nonfiction = get_nonfiction_books();
        if (count($fiction) > 0) {
            $f = array_values($fiction)[0];
            $f['_type'] = 'fiction';
            $recommendations[] = $f;
        }
        if (count($nonfiction) > 0) {
            $n = array_values($nonfiction)[0];
            $n['_type'] = 'non-fiction';
            $recommendations[] = $n;
        }
    }
}

$page_title = 'Start Here | AuthorJuanJose.io';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">
  <div class="container--narrow" style="margin:0 auto">

    <h1>Start Here</h1>
    <p class="lead">Not sure where to begin? Answer a few quick questions and we will recommend the perfect starting point.</p>

    <hr class="ornament-rule">

    <?php if (!$show_results && $step === ''): ?>
      <!-- Step 1: What interests you? -->
      <section class="start-here-step">
        <p class="section-label">Question 1 of 2</p>
        <h2>What are you most interested in?</h2>
        <div class="start-here-options">
          <a class="start-here-option" href="/start-here?step=theme&interest=fiction">
            <h3>Fiction</h3>
            <p>Immersive steampunk worlds, futuristic technology, and serialized adventures.</p>
          </a>
          <a class="start-here-option" href="/start-here?step=theme&interest=non-fiction">
            <h3>Non-Fiction</h3>
            <p>Real-world ideas, practical insight, and the thinking behind the stories.</p>
          </a>
          <a class="start-here-option" href="/start-here?step=theme&interest=both">
            <h3>Both</h3>
            <p>Show me the best of everything — fiction and non-fiction.</p>
          </a>
        </div>
      </section>
    <?php elseif (!$show_results && $step === 'theme'): ?>
      <!-- Step 2: What themes appeal to you? -->
      <section class="start-here-step">
        <p class="section-label">Question 2 of 2</p>
        <h2>What themes appeal to you most?</h2>
        <div class="start-here-options">
          <?php
          $theme_options = [
              'steampunk'   => ['Steampunk',   'Brass, gears, steam-powered possibility.'],
              'adventure'   => ['Adventure',   'High-stakes journeys, danger, and resilience.'],
              'science-fiction' => ['Science Fiction', 'Technology, futures, and alternate timelines.'],
              'historical'  => ['Historical',  'Real history reimagined through fiction.'],
              'creativity'  => ['Creativity',  'Ideas, process, and the art of making things.'],
              'technology'  => ['Technology',  'How systems work and how they change the world.'],
          ];
          foreach ($theme_options as $key => $opt): ?>
            <a class="start-here-option" href="/start-here?step=results&interest=<?php echo urlencode($interest); ?>&theme=<?php echo urlencode($key); ?>">
              <h3><?php echo htmlspecialchars($opt[0], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p><?php echo htmlspecialchars($opt[1], ENT_QUOTES, 'UTF-8'); ?></p>
            </a>
          <?php endforeach; ?>
          <a class="start-here-option" href="/start-here?step=results&interest=<?php echo urlencode($interest); ?>&theme=">
            <h3>Surprise Me</h3>
            <p>Just show me the best stuff.</p>
          </a>
        </div>
      </section>
    <?php elseif ($show_results): ?>
      <!-- Results -->
      <section class="start-here-step">
        <p class="section-label">Your Recommendations</p>
        <h2>We Think You Will Love These</h2>

        <?php if (count($recommendations) > 0): ?>
          <div class="card-grid">
            <?php foreach ($recommendations as $book): ?>
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
          <p>We are still building the catalog. Check back soon for personalized recommendations!</p>
        <?php endif; ?>

        <div class="mt-lg">
          <a class="button button--outline" href="/start-here">Start Over</a>
          <a class="button button--outline" href="/library" style="margin-left:.5rem">Browse Library</a>
        </div>
      </section>

      <div class="divider-gear"></div>

      <section class="section text-center">
        <h2>Want Early Access?</h2>
        <p class="lead" style="margin:0 auto var(--space-lg)">Join the ARC Reader Club for advance copies and insider updates.</p>
        <a class="button button--lg" href="/arc-reader-club/join">Join the ARC Reader Club</a>
      </section>
    <?php endif; ?>

  </div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
