# AGENTS.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

AuthorJuanJose.io is the official author website for Juan Jose — a steampunk science fiction author. The site features book listings, an ARC (Advance Reader Copy) Reader Club with membership features, author bio, events, journal, media, and contact pages.

## Architecture

### Frontend (PHP + HTML + CSS + JS)

PHP-based site using shared `includes/` for header, footer, config, and auth. Pages are `.php` files at the project root. CSS in `assets/css/`, JS in `assets/js/`.

**Key pages:**
- `index.php` — Homepage (hero, featured book, ARC Reader Club teaser, about teaser)
- `fiction/index.php` — Fiction catalog (dynamic from book-catalog.php)
- `fiction/*.php` — Individual fiction book pages (use includes/templates/book-fiction.php)
- `non-fiction/index.php` — Non-fiction catalog (dynamic from book-catalog.php)
- `non-fiction/*.php` — Individual non-fiction book pages (use includes/templates/book-nonfiction.php)
- `series/index.php` — Series listing
- `series/*.php` — Individual series pages (use includes/templates/series-page.php)
- `about.php` — Author biography (placeholder)
- `events.php` — Events listing (placeholder)
- `journal.php` — Author journal / blog (placeholder)
- `media.php` — Media and press (placeholder)
- `contact.php` — Contact form (placeholder)
- `privacy.php` — Privacy policy
- `404.html` — Custom error page

**ARC Reader Club (`arc-reader-club/`):**
- Public pages: landing, join (application form), how-it-works, honors-and-distinctions, FAQ
- Member pages (require auth): dashboard, current-missions, submit-review, my-distinctions, archive-record
- Login/logout with session-based member auth
- Sub-navigation for club-specific pages + member navigation when logged in

**Admin (`admin/`):**
- Admin dashboard, member management, campaign management, review moderation
- Protected by site auth-gate

**Shared PHP includes (`includes/`):**
- `header.php` — Sitewide header with dynamic page title, nav with active-state highlighting, optional ARC sub-navigation and member navigation
- `footer.php` — Sitewide footer
- `site-config.php` — Site name, navigation arrays (`$main_navigation`, `$arc_navigation`, `$member_navigation`)
- `auth-gate.php` — Session-based site authentication (login/logout via `$_SESSION['site_auth']`)
- `db.php` — SQLite PDO connection singleton + auto-schema init + tier helper functions
- `member-auth.php` — Member auth guard (checks session, loads member, redirects to login)
- `book-catalog.php` — Centralized book/series data arrays + helper functions
- `components/buy-links.php` — Reusable buy-link buttons component
- `components/related-books.php` — Reusable related books card grid component
- `templates/book-fiction.php` — Fiction book page layout template
- `templates/book-nonfiction.php` — Non-fiction book page layout template
- `templates/series-page.php` — Series page layout template

**Forms (`forms/`):**
- `storage/` — Form submission storage (should be excluded from git)

### Data
- `data/arc.sqlite` — SQLite database (gitignored, auto-created)
- `data/.htaccess` — Deny-all protection

### Assets
- `assets/css/styles.css` — Main stylesheet
- `assets/js/` — Site scripts
- `assets/images/` — Site images (including `books/` and `series/` subdirs)

## Hosting & Deployment
- Apache on shared hosting (BlueHost/cPanel)
- PHP 8.1
- `.htaccess` handles HTTPS canonicalization (non-www), `.php`/`.html` extension stripping, clean URLs, security headers, browser caching, gzip, MIME types, directory listing prevention
- No build step — deploy by uploading files directly

## Conventions
- All PHP files use `declare(strict_types=1)`
- Pages follow pattern: set `$page_title` → require header → page content → require footer
- Active nav state uses `$is_active()` closure comparing normalized URL paths
- Steampunk visual theme with gear dividers (`.divider-gear`)
- Button variants: `.button`, `.button--lg`, `.button--outline`
- Card grid layout: `.card-grid` with `.card` items

## Important Notes
- `includes/auth-gate.php` handles site-level authentication — contains session logic, handle with care
- `includes/member-auth.php` handles member-level authentication — all member pages require this
- `data/arc.sqlite` is the database — never commit, auto-created on first access
- `forms/storage/` may contain user-submitted data — never commit this directory
- `forms/mailerlite-config.php` contains API keys — never commit
- Some pages still have placeholder content (about, events, journal, media, contact)
- To add a new book: add entry to `includes/book-catalog.php`, create a 4-line PHP file in `fiction/` or `non-fiction/`
- Tier promotion is automatic: review submission triggers tier check in `includes/db.php`
