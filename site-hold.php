<?php
declare(strict_types=1);
http_response_code(200);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>AuthorJuanJose.io | Updates in Progress</title>
  <style>
    :root {
      --bg: #f5efe2;
      --panel: #fffaf1;
      --ink: #2b241d;
      --ink-light: #5c4f3d;
      --accent: #9d6a2f;
      --accent-hover: #b87a34;
      --border: #d8c8ae;
      --font-heading: Georgia, "Times New Roman", serif;
      --font-body: "Segoe UI", Arial, sans-serif;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(160deg, rgba(43,36,29,.06), rgba(157,106,47,.08)), var(--bg);
      color: var(--ink);
      font-family: var(--font-body);
      line-height: 1.6;
      padding: 1.25rem;
    }
    .panel {
      width: min(680px, 100%);
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 2rem 1.5rem;
      text-align: center;
      box-shadow: 0 8px 24px rgba(43,36,29,.12);
    }
    .brand {
      font-family: var(--font-heading);
      font-size: 1.7rem;
      font-weight: 700;
      margin: 0 0 .5rem;
      letter-spacing: .01em;
    }
    h1 {
      font-family: var(--font-heading);
      margin: .25rem 0 .75rem;
      font-size: clamp(1.45rem, 3.5vw, 2rem);
    }
    p {
      margin: 0 auto 1rem;
      max-width: 42ch;
      color: var(--ink-light);
    }
    .button {
      display: inline-block;
      margin-top: .5rem;
      padding: .65rem 1.2rem;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 700;
      background: var(--accent);
      color: #fff;
    }
    .button:hover { background: var(--accent-hover); }
    .small {
      font-size: .86rem;
      color: var(--ink-light);
      margin-top: 1rem;
    }
  </style>
</head>
<body>
  <main class="panel">
    <p class="brand">AuthorJuanJose.io</p>
    <h1>Updates in progress</h1>
    <p>The site is being finalized for public release. Thank you for your patience while we complete updates.</p>
    <a class="button" href="/contact">Contact</a>
    <p class="small">Please check back soon.</p>
  </main>
</body>
</html>
