# AGENTS.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

AuthorJuanJose.io is the official author website for Juan Jose — a steampunk science fiction author. The site features book listings, an ARC (Advance Reader Copy) Reader Club with membership features, author bio, events, journal, media, and contact pages.

## Architecture

### Frontend (PHP + HTML + CSS + JS)

PHP-based site using shared `includes/` for header, footer, config, and auth. Pages are `.php` files at the project root. CSS in `assets/css/`, JS in `assets/js/`.

**Key pages:**
- `index.php` — Homepage (hero, featured book, ARC Reader Club teaser, about teaser)
- `fiction.php` — Fiction catalog (placeholder)
- `non-fiction.php` — Non-fiction catalog (placeholder)
- `about.php` — Author biography
- `events.php` — Events listing
- `journal.php` — Author journal / blog
- `media.php` — Media and press
- `contact.php` — Contact form
- `privacy.php` — Privacy policy
- `404.html` — Custom error page

**ARC Reader Club (`arc-reader-club/`):**
- Membership system with join flow
- Sub-navigation for club-specific pages

**Shared PHP includes (`includes/`):**
- `header.php` — Sitewide header with dynamic page title, nav with active-state highlighting, optional ARC sub-navigation and member navigation
- `footer.php` — Sitewide footer
- `site-config.php` — Site name, navigation arrays (`$main_navigation`, `$arc_navigation`, `$member_navigation`)
- `auth-gate.php` — Session-based site authentication (login/logout via `$_SESSION['site_auth']`)

**Forms (`forms/`):**
- `storage/` — Form submission storage (should be excluded from git)

### Assets
- `assets/css/styles.css` — Main stylesheet
- `assets/js/` — Site scripts
- `assets/images/` — Site images

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
- `forms/storage/` may contain user-submitted data — never commit this directory
- Many pages have placeholder content ("will be added during implementation") — the site is in active development
- The ARC Reader Club section has its own sub-navigation and member authentication flow
