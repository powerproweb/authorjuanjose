# Changelog

All notable changes to AuthorJuanJose.io will be documented in this file.

## [Unreleased]

### Added
- Initial site with PHP-based page system (shared header/footer/config)
- Homepage with featured book, ARC Reader Club teaser, about section
- Page stubs: about, events, journal, media, contact, privacy
- ARC Reader Club section with membership flow
- Session-based site authentication (auth-gate)
- Custom 404 page
- Project scaffolding: .gitignore, .gitattributes, AGENTS.md, CHANGELOG.md
- BUILD_plan.md — consolidated project plan from mempalace planning docs

### Phase 2 — Book Infrastructure
- Centralized book catalog data system (`includes/book-catalog.php`) with helper functions
- Fiction book page template (`includes/templates/book-fiction.php`)
- Non-fiction book page template (`includes/templates/book-nonfiction.php`)
- Series page template (`includes/templates/series-page.php`)
- Buy-links component (`includes/components/buy-links.php`)
- Related-books component (`includes/components/related-books.php`)
- Fiction landing page (`fiction/index.php`) with catalog grid, coming-soon, and series sections
- Non-fiction landing page (`non-fiction/index.php`) with catalog grid and empty state
- Series listing and detail pages (`series/`)
- Sample book page: Michael Strogoff (`fiction/michael-strogoff.php`)
- Image asset directories: `assets/images/books/`, `assets/images/series/`
- CSS for book detail layouts, book cards, buy links, reviews, tags, badges, reading order, series items

### Phase 3 — Member System
- SQLite database via PDO (`data/arc.sqlite`) with auto-schema initialization
- 5 database tables: members, campaigns, campaign_invites, reviews, distinctions
- Member auth guard (`includes/member-auth.php`) with session-based authentication
- Login page with bcrypt password verification and session regeneration
- Member dashboard with tier badge, progress bar, active campaigns, recent reviews
- Current Missions page with campaign accept/decline actions
- Submit Review page with auto review-count increment and tier auto-promotion
- My Distinctions page with tier progress, earned/locked states, and history log
- Archive Record page with participation summary and full history
- Admin panel: dashboard (`/admin`), members, campaigns, reviews management
- Admin member approval with temporary password generation
- Admin campaign creation, activation, bulk member invite, close
- Admin review verification/moderation
- Join form integration: DB insert alongside existing MailerLite/file storage
- CSS for dashboard tier display, progress bars, campaign cards, status badges, admin lists
- `.gitignore` updated for `data/*.sqlite` and `forms/mailerlite-config.php`

### Removed
- Root `fiction.php` and `non-fiction.php` (replaced by `fiction/index.php` and `non-fiction/index.php`)
