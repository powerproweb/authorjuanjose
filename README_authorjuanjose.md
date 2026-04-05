# AuthorJuanJose.io

Official author website for Juan Jose — a steampunk science fiction author.

The site features book listings, an ARC (Advance Reader Copy) Reader Club with membership features, author bio, events, journal, media, and contact pages.

---

## Tech Stack

- **Language:** PHP 8.1 (`declare(strict_types=1)` throughout)
- **Server:** Apache on shared hosting (BlueHost / cPanel)
- **Frontend:** PHP + HTML + CSS + vanilla JS
- **Deployment:** No build step — deploy by uploading files directly via cPanel or FTP/SFTP

---

## Project Structure

```
/
├── index.php               # Homepage
├── fiction.php             # Fiction catalog
├── non-fiction.php         # Non-fiction catalog
├── about.php               # Author biography
├── events.php              # Events listing
├── journal.php             # Author journal / blog
├── media.php               # Media and press
├── contact.php             # Contact form
├── privacy.php             # Privacy policy
├── 404.html                # Custom error page
│
├── arc-reader-club/        # ARC Reader Club section (membership + sub-nav)
├── includes/               # Shared PHP includes
│   ├── header.php          # Sitewide header with dynamic title and nav
│   ├── footer.php          # Sitewide footer
│   ├── site-config.php     # Site name and navigation arrays
│   └── auth-gate.php       # Session-based auth gate (gitignored)
│
├── assets/
│   ├── css/styles.css      # Main stylesheet
│   ├── js/                 # Site scripts
│   └── images/             # Site images
│
├── forms/
│   └── storage/            # Form submission storage (gitignored)
│
├── .htaccess               # Apache config: HTTPS, clean URLs, security headers, caching
├── AGENTS.md               # Warp AI agent guidance
└── CHANGELOG.md            # Project changelog
```

---

## Page Pattern

Every PHP page follows this structure:

```php
<?php
declare(strict_types=1);
$page_title = 'Page Title';
require 'includes/header.php';
?>

<!-- page content -->

<?php require 'includes/footer.php'; ?>
```

---

## Auth Gate

While the site is under construction, `includes/auth-gate.php` shows a styled login page to all visitors. Authenticated admins browse normally.

To disable it when the site goes public, either delete the file or set `SITE_AUTH_GATE_ENABLED = false` before including `header.php`.

> `auth-gate.php` is gitignored — never commit it.

---

## Deployment

1. Edit files locally.
2. Upload changed files to BlueHost via **cPanel File Manager** or an FTP/SFTP client.
3. No build or compilation step required.

---

## Conventions

- Steampunk visual theme — gear dividers (`.divider-gear`)
- Button variants: `.button`, `.button--lg`, `.button--outline`
- Card grid layout: `.card-grid` with `.card` items
- Active nav state managed via `$is_active()` closure in `header.php`

---

## Status

Site is in active development. Many pages contain placeholder content pending full implementation.
