# 2026-05-29 Recovery and Deployment Log
Timezone: UTC
Secrets: redacted

## Scope
- Site and admin credential recovery
- Admin authentication hardening
- ARC submission visibility fix in admin inbox
- Production deployment and validation passes
- Issue closeout documentation

## Detailed timestamped log
- 2026-05-29 21:48 - Started credential recovery investigation for site and admin access.
- 2026-05-29 21:49 - Read `includes/admin-auth.php` and confirmed bcrypt hash usage with optional env override (`AJJ_ADMIN_USER` / `AJJ_ADMIN_PASS_HASH`).
- 2026-05-29 21:50 - Checked recent git history for `includes/admin-auth.php` to trace credential baseline.
- 2026-05-29 21:51 - Retrieved project auth references and backups for comparison.
- 2026-05-29 21:55 - Generated new bcrypt hash for the requested password and updated local fallback hash in `includes/admin-auth.php`.
- 2026-05-29 21:56 - Ran syntax validation on updated auth file.
- 2026-05-29 21:58 - Investigated continued login failure and found second auth path still stale.
- 2026-05-29 22:00 - Synced credentials in both:
  - `includes/admin-auth.php`
  - `includes/auth-gate.php`
- 2026-05-29 22:01 - Confirmed local auth path alignment was correct.
- 2026-05-29 22:06 - Began live endpoint verification for `/admin/`.
- 2026-05-29 22:08 - Reproduced repeated `401` from live endpoint for candidate credentials.
- 2026-05-29 22:10 - Captured live auth challenge headers and started deep production file inspection over FTP.
- 2026-05-29 22:11 - Verified deployed `public_html/includes/admin-auth.php` and `public_html/admin/index.php` content.
- 2026-05-29 22:12 - Root cause found: host path stripped `Authorization` headers before PHP, breaking Basic auth path.
- 2026-05-29 22:14 - Implemented form+session fallback in `includes/admin-auth.php` while preserving header-based path.
- 2026-05-29 22:15 - Deployed `includes/admin-auth.php` to production via FTP upload.
- 2026-05-29 22:16 - Validated live behavior:
  - `/admin/` returned `200` login form
  - end-to-end login succeeded
- 2026-05-29 22:17 - Removed temporary live debug endpoint and local debug artifact.
- 2026-05-29 22:27 - Started ARC inbox issue investigation after report that ARC requests were missing in admin panel.
- 2026-05-29 22:28 - Read:
  - `forms/submit-arc-application.php`
  - `admin/form-submissions.php`
  - `includes/contact-inbox-db.php`
  - `includes/db.php`
- 2026-05-29 22:30 - Root cause found:
  - ARC join handler wrote to `members` only
  - admin inbox page reads `contact_submissions`
  - existing member email re-submissions could silently skip visible review records
- 2026-05-29 22:31 - Performed deep data pass on production storage:
  - listed production data dir
  - downloaded read-only copies of `arc.sqlite` and `contact-inbox.sqlite`
  - confirmed mismatch between member submissions and inbox records
  - inspected `forms/storage/arc-applications.ndjson` for submission events
- 2026-05-29 22:33 - Implemented ARC handler fix in `forms/submit-arc-application.php`:
  - mirror ARC submissions into `contact_submissions` with `inquiry_type='arc'`
  - add inbox event log entry
  - preserve existing member status on re-application, update profile fields only
  - include ticket ref in user confirmation/queued notices
- 2026-05-29 22:34 - Deployed updated ARC handler to production.
- 2026-05-29 22:34 - Ran production submission test with new email:
  - pending member created
  - ARC ticket created in inbox
- 2026-05-29 22:35 - Ran production submission test with existing email:
  - ARC ticket created in inbox
  - existing member status unchanged
- 2026-05-29 22:36 - Re-validated production DB snapshots to confirm record counts and latest ARC tickets.
- 2026-05-29 22:45 - User confirmed dashboard and submission flow working; requested records be retained.
- 2026-05-29 22:51 - Investigated follow-up password reset report, traced member login/status gate path.
- 2026-05-29 22:52 - User confirmed reset/login path working.
- 2026-05-29 22:57 - Added fix summary to `CHANGELOG.md` under `Unreleased > Fixed`.
- 2026-05-29 22:58 - Ran syntax checks:
  - `forms/submit-arc-application.php`
  - `includes/admin-auth.php`
  - `includes/auth-gate.php`
- 2026-05-29 22:59 - Re-verified production deployed file signatures for:
  - admin auth form fallback logic
  - ARC inbox mirror logic
- 2026-05-29 23:00 - Troubleshooting and deployment process closed.

## Deployment detail
- Deployment method used today: direct FTP upload to `public_html`
- Updated production files:
  - `public_html/includes/admin-auth.php`
  - `public_html/forms/submit-arc-application.php`
- Related local files modified during closure:
  - `includes/auth-gate.php`
  - `CHANGELOG.md`

## GitHub activity audit
- No GitHub CLI/API operations were executed in this recovery flow.
- No branch push, PR creation, or merge action was performed during this session.
- No GitHub deployment workflow was invoked for these fixes.
