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

## Deploy

GitHub → Actions → **Deploy to SiteGround** → Run workflow. Tick **first_deploy** the first time, then open `/install/`. See BACKEND.md §9.
