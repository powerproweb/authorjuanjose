# AuthorJuanJose.io — BUILD Plan
> Consolidated from site plan (3/17/2026) | Last updated: 4/10/2026 (ALL PHASES COMPLETE)

---

## Vision
AuthorJuanJose.io is a **premium author platform** — not a brochure site.
Positioned as **one unified author brand with two clear lanes**: Fiction and Non-Fiction.
Concept: *"One master brand, two doorways"* — distinct content tracks sharing a unified steampunk design system.
Tagline direction: *"Stories. Ideas. Worlds. Insight."*

---

## Brand & Visual Direction
- Steampunk visual theme throughout — gear dividers, brass/copper tones, mechanical motifs
- Accent color differentiation for Fiction vs. Non-Fiction lanes
- Button variants: `.button`, `.button--lg`, `.button--outline`
- Card grid layouts: `.card-grid` with `.card` items
- Typography and design should convey: premium, literary, immersive
- Fiction lane: imagination, adventure, worldbuilding
- Non-Fiction lane: insight, authority, real-world thinking

---

## Site Architecture

### Primary Navigation
**Home · Fiction · Non-Fiction · About · Journal · Media · Events · Contact · Library · Gallery · Start Here · ARC Reader Club**

### Fiction Section
- Fiction Landing Page — define the fiction brand, showcase featured titles
- Featured Titles — highlighted books
- All Fiction Titles — complete catalog
- Series Pages — series/world groupings
- Individual Fiction Book Pages:
  - Cover, hook/headline, synopsis
  - Series info, reading order
  - Buy links, excerpt
  - Reviews, related books
  - Character/world/timeline content (optional)
  - Newsletter CTA

### Non-Fiction Section
- Non-Fiction Landing Page — define the non-fiction brand, build authority
- Featured Titles
- All Non-Fiction Titles
- Essays / Articles
- Resources
- ARC Reader Club CTA integration
- Individual Non-Fiction Book Pages:
  - Cover, core premise
  - What readers will learn, key themes
  - Buy links, sample chapter/excerpt
  - Endorsements, related resources
  - ARC Reader Club CTA

### About
- Author biography (concise + extended)
- Fiction + non-fiction positioning
- Mission / Philosophy
- "Why I Write" section (optional)

### Journal
- Author blog / updates system
- Behind-the-scenes content
- Writing updates and progress

### Media
- Press / Media Kit
- Interview Topics
- Author Photos
- Book One-Sheets
- Downloadable Press Assets
- Testimonials / Endorsements

### Events
- Upcoming appearances and launch events
- Event inquiry form (readings, interviews, talks, speaking topics)

### Contact
- Form with category routing:
  - General Contact
  - Media Inquiry
  - Speaking Inquiry
  - Reader Message
  - ARC / Review-related question

### Coloring Book Gallery
- Members upload photos of completed coloring book pages
- Submissions tied to user accounts
- Public gallery showcasing reader artwork
- Account-based posting (upload, caption, display under profile)
- Image handling: upload, resize/optimize, storage
- Moderation workflow before public display (optional)
- Social features: likes, featured picks, sorting by recent/popular

### Homepage
- Designed around a **split entry experience**
- Hero section with primary CTAs
- Featured Works section — top books from each lane
- ARC Reader Club teaser
- About the Author teaser
- Newsletter signup
- Content: concise bio, fiction + non-fiction positioning, author mission/themes

---

## ARC Reader Club System
Positioned as a **high-end private reader society** inside the author brand.

### Public Pages (IMPLEMENTED)
- **Landing Page** — conversion-focused copy, what/why/who/benefits
- **Join / Apply** — full application form (CSRF, validation, flash messages, MailerLite integration, honeypot, language preference)
- **How It Works** — five-step process, expectations
- **Honors & Distinctions** — four-tier progression system
- **FAQ** — accordion-style common questions

### Four-Tier Distinction System (IMPLEMENTED IN COPY)
1. **Tier I — Copper Cog Commendation** — first ARC review completed
2. **Tier II — Silver Steamwright Honors** — 3–5 reviews, proven reliability
3. **Tier III — Golden Gearmaster Distinction** — 6–9 reviews, dedicated supporter
4. **Tier IV — Obsidian Chrononaut Medal of Honor** — founding/elite contributor

### Member Pages (IMPLEMENTED — Phase 3)
- **Login** — email/password auth, bcrypt, session regeneration, status-aware errors
- **Logout** — session destroy + redirect
- **Dashboard** — welcome, tier badge + progress bar, active campaigns, recent reviews, quick-link cards
- **Current Missions** — campaign list with accept/decline, submit review links, status badges
- **Submit Review** — campaign select, platform, URL, text; auto-increments reviews, auto-promotes tiers
- **My Distinctions** — current tier + progress, all 4 tiers (earned/locked/in-progress), distinction history
- **Archive Record** — summary stats, full campaign and review history

### Member Database (IMPLEMENTED — Phase 3)
- **SQLite** via PDO at `data/arc.sqlite` (protected by `.htaccess` deny-all)
- **5 tables**: members, campaigns, campaign_invites, reviews, distinctions
- **Auto-schema**: tables created on first connection
- **Tier auto-promotion**: 1→Copper Cog, 3→Silver Steamwright, 6→Golden Gearmaster, 10→Obsidian Chrononaut
- **Auth guard**: `includes/member-auth.php` — session check, member data load, redirect if unauthenticated
- **Join form integration**: DB insert alongside MailerLite/file storage

### Admin Panel (IMPLEMENTED — Phase 3)
- **Dashboard** (`/admin`) — stats: members, pending approvals, campaigns, unverified reviews
- **Members** (`/admin/members`) — filter by status, approve (generates temp password), suspend, reactivate, reset password
- **Campaigns** (`/admin/campaigns`) — create, activate, invite all members, close
- **Reviews** (`/admin/reviews`) — filter by verified/unverified, verify/unverify

### ARC System Layers
1. Structure — application flow, membership rules, tier definitions ✅
2. Content — page copy, email sequences, campaign messaging (email sequences pending)
3. Technical — form handling, MailerLite integration, member auth, dashboard ✅
4. Visual — tier badges, emblems, certificates (physical items pending)

### Future ARC Features
- Onboarding email sequence
- Language-specific follow-up flows (English / Spanish)
- Printable certificates, commemorative items
- Digital honor roll
- Branded digital badges

---

## Platform Features (Planned)
- Segmented newsletter signup (fiction vs. non-fiction interest)
- Filtered library by fiction / non-fiction / genre / topic
- "Start Here" recommendation engine
- Related reading across categories
- Search indexing books, essays, interviews, events
- Tagging system for themes, topics, genres, series, subjects
- ~~Press kit for both literary and non-fiction sides~~ (structure implemented Phase 1, downloadable assets pending)
- ~~Event inquiry form with type distinction~~ (contact form with category routing implemented Phase 1)

---

## Current State Assessment (4/10/2026 — ALL PHASES COMPLETE)

### ✅ Site is Launch-Ready
All 6 development phases are complete. The site is fully functional behind the auth-gate.
To launch: set `SITE_AUTH_GATE_ENABLED = false` in `auth-gate.php`.

### Complete Feature Set
- **45+ CSS sections**, **10 DB tables**, **9 admin pages**, **12+ nav items**
- **SEO**: per-page meta descriptions, Open Graph, Twitter Cards, canonical URLs, JSON-LD, robots.txt, sitemap generator
- **Accessibility**: skip-to-content link, aria-current on nav, focus-visible styles, keyboard-navigable lightbox
- **Performance**: DB indexes on hot columns, asset versioning (cache-busting), .htaccess caching/compression
- **All content pages**: Homepage, About, Fiction, Non-Fiction, Events, Journal, Media, Contact, Privacy
- **Book infrastructure**: catalog system, fiction/non-fiction/series templates, buy links, related books, cross-category suggestions
- **Member system**: SQLite auth, 5 member pages, tier auto-promotion, campaign/review tracking
- **Engagement**: newsletter signup (segmented), email templates (EN+ES), email queue/cron, campaign notifications, gallery
- **Discovery**: tag cloud, filtered library, search with highlighted excerpts, Start Here recommendation engine
- **Admin panel**: members, campaigns, reviews, email queue, gallery moderation, search index, sitemap generator
- **Honor roll**: public page showing distinguished members
- **Press kit**: download links for author photos, book one-sheets, press assets

---

## Development Roadmap

### Phase 1 — Content Pages ✅ COMPLETE
1. ~~About — Author biography, mission, "Why I Write"~~
2. ~~Contact — Category-routed inquiry form (5 types) + form handler~~
3. ~~Media — Press bio, interview topics, press kit structure~~
4. ~~Events — Event type cards, upcoming events, booking inquiry~~
5. ~~Journal — PHP-array entry system with categories and welcome post~~
6. Fiction/Non-Fiction Landings — already implemented in Phase 2

### Phase 2 — Book Infrastructure ✅ COMPLETE
1. ~~Individual fiction book page template~~
2. ~~Individual non-fiction book page template~~
3. ~~Series/collection page template~~
4. ~~Buy-link system~~
5. ~~Related books / cross-category suggestions~~

### Phase 3 — Member System ✅ COMPLETE
1. ~~Member authentication (login/session)~~
2. ~~Member dashboard~~
3. ~~Current Missions (active ARC campaigns)~~
4. ~~Submit Review flow~~
5. ~~My Distinctions (tier tracking)~~
6. ~~Archive Record (participation history)~~
7. ~~Admin tools for managing members and campaigns~~

### Phase 4 — Engagement Features ✅ COMPLETE
1. ~~Newsletter signup (segmented, sitewide footer)~~
2. ~~Email templates (8 templates, EN + ES) + queue + cron~~
3. ~~Campaign notifications (Invite & Notify + in-app)~~
4. ~~Language-specific flows~~
5. ~~Coloring Book Gallery (upload, GD resize, lightbox, admin moderation)~~

### Phase 5 — Discovery & Search ✅ COMPLETE
1. ~~Tagging system (tag cloud + tag view pages, clickable tag pills)~~
2. ~~Filtered library (/library with type/tag/status filters)~~
3. ~~Search indexing + search page with highlighted excerpts~~
4. ~~"Start Here" recommendation engine (2-step question flow)~~
5. ~~Cross-category suggestions ("From the Other Side" on book pages)~~

### Phase 6 — Polish & Launch ✅ COMPLETE
1. ~~SEO: meta descriptions, OG tags, Twitter Cards, canonical URLs, JSON-LD~~
2. ~~robots.txt + admin sitemap generator~~
3. ~~Performance: DB indexes, asset versioning (cache-busting)~~
4. ~~Accessibility: skip link, aria-current, focus-visible styles~~
5. ~~Press kit download links + honor roll page~~
6. ~~Auth-gate toggle ready (set SITE_AUTH_GATE_ENABLED = false to launch)~~

---

## Tech Stack
- **Language:** PHP 8.1+ (`declare(strict_types=1)`) — local dev on PHP 8.4
- **Server:** Apache on BlueHost (cPanel)
- **Frontend:** PHP + HTML + CSS + vanilla JS
- **Database:** SQLite via PDO (`data/arc.sqlite`)
- **Email:** MailerLite (ARC signups)
- **Deployment:** Direct upload via cPanel / FTP/SFTP — no build step
- **Auth:** Session-based (`auth-gate.php` for dev, member auth for ARC via `member-auth.php`)

---

## Source Documents
- `authorjuanjose-io-site-plan_20260317_1343.md` (mempalace: authorjuanjose_website/planning)
- `arc-reader-club-details_20260317_1330.md` (mempalace: authorjuanjose_website/architecture)
- `steampunk-arc-club-names_20260317_1236.md` (mempalace: authorjuanjose_website/decisions)
