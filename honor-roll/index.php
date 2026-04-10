<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = get_db();

// Get members with at least one distinction, ordered by highest tier
$members = $pdo->query('
    SELECT m.name, m.tier
    FROM members m
    WHERE m.tier > 0 AND m.status = "active"
    ORDER BY m.tier DESC, m.name ASC
')->fetchAll();

$page_title = 'Honor Roll | AuthorJuanJose.io';
$page_description = 'Recognized members of the ARC Reader Club who have earned distinction tiers through active participation and honest reviews.';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="container page-shell">

  <h1>Honor Roll</h1>
  <p class="lead">Recognizing the dedicated members of the ARC Reader Club who have earned distinction through their participation and honest reviews.</p>

  <hr class="ornament-rule">

  <?php if (count($members) > 0): ?>
    <div class="tier-grid">
      <?php foreach ($members as $m): ?>
        <?php $css = get_tier_css_class((int)$m['tier']); ?>
        <div class="tier-card tier-card--<?php echo $css; ?>">
          <div class="tier-icon tier-icon--<?php echo $css; ?>">&#9881;</div>
          <h3 style="margin-bottom:var(--space-xs)"><?php echo htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <p class="tier-rank">Tier <?php echo (int)$m['tier']; ?></p>
          <p style="font-size:.88rem;color:var(--ink-light)"><?php echo htmlspecialchars(get_tier_short((int)$m['tier']), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="panel">
      <p>The honor roll is just getting started. As ARC Reader Club members participate in campaigns and submit reviews, their names and earned distinctions will appear here.</p>
      <p><a class="button" href="/arc-reader-club/join">Join the ARC Reader Club</a></p>
    </div>
  <?php endif; ?>

  <div class="divider-gear"></div>

  <section class="section text-center">
    <h2>Earn Your Place</h2>
    <p class="lead" style="margin:0 auto var(--space-lg)">Join the ARC Reader Club, participate in campaigns, and rise through the distinction tiers.</p>
    <a class="button button--lg" href="/arc-reader-club/join">Join the Club</a>
    <a class="button button--outline button--lg" href="/arc-reader-club/honors-and-distinctions" style="margin-left:.5rem">View Tier System</a>
  </section>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
