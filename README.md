# FSCD — Website + Dashboard

The Foundation for Sustainable Community Development website, staged at **ahrons23.sg-host.com**. It replaces a WordPress/Elementor build with plain PHP 8 + MySQL and an app-style dashboard at `/admin/`.

- **`BRIEF.md`** — the project instructions: what to build and how to check it.
- **`BACKEND.md`** — the technical reference for the backend.
- **`app/seed/fscd/`** — the 14 original pages (prepared from the Elementor HTML), their images and `manifest.json`.

## Local development

```bash
npm install
php app/cli/install.php --driver=sqlite --username=admin --email=you@example.com --password='choose-one'
php -S 127.0.0.1:8080 index.php     # site http://127.0.0.1:8080, dashboard /admin/
npm run build                       # after CSS/JS edits; commit the .min files
```

## How the pages work

- Each of the 14 pages is stored in **Dashboard → Pages** as raw HTML (template "Raw HTML page"), exactly as it was in its Elementor HTML widget. Only **Administrators** can edit them.
- Tokens in a page: `{{header}}` and `{{footer}}` (shared, edited in **Settings → Header & Footer**; the current page's menu item is marked active automatically), `{{media:file-name.jpg}}` (a Media Library file by its original name; *Replace file…* keeps the name, so every page updates), `{{form_token}}` (spam protection for the built-in forms).
- Every save keeps the previous version under **Revisions** (latest 30) with *Restore*.
- Forms post to `/api/form` and land in **Form Inbox**; notifications go to Settings → General → *Form notification email*.
- `php app/cli/import-fscd.php [--force]` re-imports from `app/seed/fscd/` (new media files are always added; `--force` overwrites pages and the header/footer, keeping the old content as a revision).

## Checks

```bash
npm install && php -S 127.0.0.1:8080 index.php &
node scripts/visual-check.js       # pixel comparison of every page vs the original (scripts/visual-report/report.md)
node scripts/behaviour-check.js    # menus, sticky header, mobile menu, contact topic router
```

## Deploy

GitHub → Actions → **Deploy to SiteGround** → Run workflow. Tick **first_deploy** the first time, then open `/install/`. See BACKEND.md §9.
