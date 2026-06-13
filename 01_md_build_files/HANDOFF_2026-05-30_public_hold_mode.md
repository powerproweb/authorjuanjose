# AuthorJuanJose.io Handoff
Date: 2026-05-30
Status: Public hold page is active, development preview route is active, live deploy path is stable.

## What was done in this session
1. Fixed GitHub Actions deploy failures caused by missing repo-level deploy secrets.
2. Confirmed deploy pipeline now succeeds on `main`.
3. Resolved live menu issues:
   - Auth gate was disabled by default in shared header flow.
   - Dropdown behavior was fixed so submenu links are clickable.
   - Asset version cache busting was bumped.
4. Synced production and source control:
   - Commit pushed to `main`: `b028e84`
5. Added temporary public hold mode while continuing internal work:
   - Public root `/` now serves `site-hold.php`
   - Real homepage remains accessible for development preview via:
     - `/index?preview=1`

## Files changed for hold mode
- `.htaccess`
- `site-hold.php`

## Live behavior right now
- `https://authorjuanjose.io/` shows the temporary hold page.
- `https://authorjuanjose.io/index?preview=1` shows the real homepage for ongoing work.

## How to turn hold mode off when ready for full public release
1. Edit `.htaccess` and remove this temporary block:
   - `# Temporary public holding page for homepage while updates are in progress.`
   - `# Owner preview of real homepage remains available at /index.php?preview=1`
   - `RewriteCond %{REQUEST_URI} ^/$`
   - `RewriteCond %{QUERY_STRING} !(^|&)preview=1(&|$) [NC]`
   - `RewriteRule ^$ /site-hold.php [L]`
2. Optional cleanup:
   - Delete `site-hold.php` after confirming root traffic is restored.
3. Verify after change:
   - `/` should show the real homepage.
   - `/index?preview=1` can remain usable, or be retired later by removing the preview exception rule in the index canonical redirect section.

## Optional hardening still pending before full public launch
1. Publish a real `sitemap.xml` at root to match `robots.txt`.
2. Replace media page directory links with real downloadable assets or valid landing pages.
3. Move admin credentials out of code to environment variables and add login attempt throttling to admin auth.
