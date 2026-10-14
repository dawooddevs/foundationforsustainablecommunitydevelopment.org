# Backend Reference — FSCD Website

**Staging:** https://ahrons23.sg-host.com (SiteGround)  
**Kit:** `fscd-site-kit.zip`

> This is the **technical reference for the backend**. The project instructions (what to build, in what order, and how to check it) are in **`BRIEF.md`**; read that first. Where the two differ, BRIEF.md wins.

This guide is written for Claude Code. It describes a **finished backend** that you should use as-is, so you can spend your time on the frontend: design, templates and content.


---

## 1. What you get

| Area | What it does |
|---|---|
| Dashboard `/admin/` | Single-page app (vanilla JS, AJAX) with dark mode, Ctrl/Cmd+K search and Ctrl/Cmd+S save. Areas: **Treatments** (the services), **Providers**, **Pages**, **Testimonials**, **FAQs**, **Locations**, **Media Library**, **Form Inbox**, **Users & Roles**, **Settings**, **Redirects** and **Activity Log**. It also has a home screen with page-view charts, a website health checklist and a "needs review" queue. |
| Content editing | Rich-text editor, drag-to-reorder, drafts, a "needs review" flag, live preview of drafts (`?preview=1` for signed-in users), and SEO title and description with a Google preview |
| Media library | Drag-and-drop uploads up to 100 MB (images, PDFs, video) with progress bars. Images get automatic WebP versions and thumbnails. Also: alt text, bulk delete, "Replace file…" (keeps every reference), and **Auto-assign** (matches images to content by file name, see §6). |
| Forms | Built-in appointment, contact and benefits-check forms. Each is replaced by a GoHighLevel embed when one is pasted in Settings. Submissions are stored in the Form Inbox, emailed to the notification address and optionally POSTed as JSON to a GoHighLevel webhook. Spam protection: a signed time token and a honeypot field. Inbox: CSV export, plus call, text and email buttons. |
| SEO | Title and meta, canonical URL, Open Graph, and `robots` noindex while staging. JSON-LD schema: MedicalBusiness organization, locations with opening hours, breadcrumbs, FAQ and service pages. Plus `sitemap.xml`, `robots.txt`, an HTML sitemap and a 301/302 redirect manager with hit counts. |
| Users | Roles: **Administrator** (everything), **Editor** (content, media, inbox, redirects), **Author** (drafts and uploads) and **Viewer** (read-only). Logins are rate-limited, sessions are HttpOnly and SameSite with a 12-hour idle timeout, and every API call checks a CSRF token. |
| Analytics | A cookie-free daily page-view counter that skips bots, shown in the dashboard. A GA4 ID can be added in Settings. |
| Installer `/install/` | Creates the tables (MySQL or SQLite), loads the seed content, creates the admin account, writes `app/config.php` and then locks itself. |
| Migrations | `app/migrations/*.php` are one-time content and data updates. Each runs once, in the deploy. |
| Deploy | GitHub Action "Deploy to SiteGround": PHP lint, asset build, rsync over SSH and migrations. It never touches the config, database or uploads. |

The dashboard looks the same as the reference build. Its accent colours follow **Settings → Branding** (`color_primary` and `color_accent`), so it takes on the practice's colours automatically.

**Not included:** the frontend design and the content. `app/views/`, `assets/css/site.css` and `assets/js/site.js` are a **plain scaffold**. They render every data type correctly so the site works end to end, but they are not a design. Replace them.

---

## 2. Getting started

### 2.1 New repository

Unzip the kit and move the contents of `fscd-site/` to the repository root, **including hidden files**: `.github/`, `.htaccess`, `.user.ini` and `.gitignore`. Then commit.

### 2.2 Repository that already has a frontend

1. Add the kit's files.
2. Port your frontend into the **frontend layer** (§3): your page markup goes into `app/views/pages/*.php`, the shared shell into `app/views/layout.php` and the partials, and your CSS and JS into `assets/`.
3. Replace hard-coded text, images and lists with the data calls in §5, so they become editable in the dashboard.
4. Keep the hooks marked `KEEP` in `app/views/layout.php`.

A static HTML design converts directly: wrap each page in a view, swap repeated content (services, team, FAQs, testimonials, contact details) for loops over the data, and use `url()`, `asset()` and `img()` for links and images.

### 2.3 Local development

```bash
npm install
php app/cli/install.php --driver=sqlite --username=admin --email=you@example.com --password='…'
php -S 127.0.0.1:8080 index.php     # site http://127.0.0.1:8080, dashboard /admin/
npm run build                       # rebuilds site.min.css and site.min.js; commit the .min files
php app/cli/install.php … --force   # wipe and reinstall the local SQLite database
```

---

## 3. Which files are yours and which belong to the backend

**Frontend: change freely.**

- `app/views/layout.php`, `app/views/pages/*.php` and `app/views/partials/*.php`. Keep the `KEEP` hooks in `layout.php`, and keep `partials/form.php` working (§7).
- `assets/` (CSS, JS, images and fonts). The dashboard does **not** depend on anything in `assets/`.
- `app/seed/services.php` and `app/seed/seed.php`: the content loaded at install (§8).
- `app/migrations/`: content changes after install (§8).
- `app/lib/Content.php` constants:
  - `SERVICE_BASE` and `PROVIDER_BASE` (URL prefixes)
  - `CATEGORIES` (service categories)
  - `FAQ_GROUPS`
  - `PROVIDER_TYPES`
  - `BODY_AREAS`
  - `FORM_GROUPS` (patient-form PDFs)
  - `VIDEOS`
- `app/lib/Settings.php` `defaults()` and `app/lib/Resources.php` `settings()`, when you add a setting (§5.4).
- `app/lib/Forms.php` `fields()` and `concerns()`: the built-in form fields.

**Backend: do not rewrite.** Small, careful fixes are fine.

- `index.php` (router)
- `app/bootstrap.php`
- `app/schema.php`
- `app/lib/` (`DB`, `Auth`, `Media`, `Html`, `Seo`, `Installer`, `helpers`, and the rest of `Content`, `Forms` and `Resources`)
- `admin/` (dashboard and API)
- `install/`
- `app/cli/`
- `.github/workflows/deploy.yml`
- `.htaccess` and `.user.ini`

`app/config.php`, the database, sessions, logs and `uploads/` are created on the server and are git-ignored. **Never commit them.**

---

## 4. Routing and URLs

`index.php` handles the following, in this order:

1. Redirects from the dashboard.
2. `/robots.txt`, `/sitemap.xml`, `/api/form` (form posts) and `/api/treatments` (JSON service cards for "show more").
3. Trailing slashes: page URLs always end in `/`.
4. Content routes:

| URL | View | Variables |
|---|---|---|
| `/` | `pages/home.php` | — |
| `/{SERVICE_BASE}/{slug}/` (default `/services/…`) | `pages/service.php` | `$service` (row) |
| `/{PROVIDER_BASE}/{slug}/` (default `/team/…`) | `pages/provider.php` | `$provider` (row) |
| `/{slug}/` (any published page) | `pages/{template}.php`, where `template` is one of `treatments`, `appointment`, `contact`, `sitemap`, `legal`, else `pages/page.php` | `$page` (row) |
| anything else | `pages/404.php` (HTTP 404) | — |

Each view's output is wrapped in `layout.php` (`$content`, `$path`).

- **Seeded pages:**
  - `/services/` (template `treatments`)
  - `/book-appointment/`
  - `/contact-us/`
  - `/privacy-policy/`, `/terms-and-conditions/`, `/medical-disclaimer/`
  - `/sitemap/`
- **Other pages:** add them in **Dashboard → Pages**, for example About, New Patients and FAQ. They render with `pages/page.php` unless you add a template.
- **New page template:** to give a page its own layout (for example `about`), create `app/views/pages/about.php`, add `'about'` to the `$templates` list in `index.php`, and add it to the Template options in `Resources.php` (`pages` → `template`).
- **Matching the old site's URLs:**
  - Set `SERVICE_BASE` and `PROVIDER_BASE` in `app/lib/Content.php`, and rename the services index page's slug to match.
  - Add 301 redirects for any old URL that changes (Dashboard → Redirects, or the redirects list in `seed.php`).
  - Do this before launch to keep search rankings.

---

## 5. The template API (what views can call)

### 5.1 Helpers (`app/lib/helpers.php`)

| Call | Returns |
|---|---|
| `e($s)` | HTML-escaped string. **Escape everything that isn't trusted HTML.** |
| `url('path/')` | Site-relative URL; works when the site is installed in a subfolder |
| `abs_url('path/')` | Absolute URL (canonical tags, schema) |
| `asset('css/site.css')` | `/assets/…` URL with a cache-busting version |
| `media_url($path)` | URL for an uploaded file, versioned by modification time |
| `img($path, $alt, $attrs)` | Responsive image (`<picture>` with WebP, width and height, lazy loading). `$attrs` can override `loading`, add `class`, `fetchpriority`, `style` and so on. |
| `icon('name', 'class')` | Inline SVG. Names are the keys in `app/lib/icons.php`. |
| `setting('key')` | A setting's value (§5.4) |
| `partial('name', [vars])` | Render `app/views/partials/name.php` |
| `render('view', [vars])` | Render a view to a string |
| `tel_href($phone)`, `sms_href($phone)` | `tel:+1…` and `sms:+1…` links |
| `lines($text)`, `csv_list($text)`, `json_list($json)` | Split multi-line, comma-separated or JSON fields into arrays |
| `str_limit($s, $n)`, `plural($n, 'one', 'many')`, `slugify($s)` | Text utilities |
| `Html::clean($html)` | Sanitised rich text from the dashboard. **Always use it to output editor HTML.** |

### 5.2 Content (`app/lib/Content.php`)

All queries return only **published** rows, in dashboard order.

| Call | Returns |
|---|---|
| `Content::services()` | All services |
| `Content::servicesByCategory()` | `[category => [service, …]]` in `CATEGORIES` order |
| `Content::service($slug)` | One service |
| `Content::servicesBySlugs([...])` | Services in the given order |
| `Content::explorerServices('featured')` | The services picked in Settings → Homepage → Featured. With a category key, that category's services. |
| `Content::related($service, 4)` | The service's chosen related services, then others from the same category |
| `Content::serviceUrl($s)`, `Content::providerUrl($p)` | Public URLs |
| `Content::categoryLabel($key)`, `Content::serviceIcon($s)` | Category label, and the service's icon name |
| `Content::providers()`, `Content::provider($slug)` | Team members |
| `Content::page($slug)` | A page row |
| `Content::locations()` | Offices |
| `Content::hours($loc)` | `[['days' => 'Monday – Friday', 'time' => '8:00 AM – 5:00 PM'], …]` |
| `Content::hoursTable($locs)` | Hours merged across locations, as table rows |
| `Content::fullAddress($l)`, `Content::mapsDirections($l)`, `Content::mapEmbed($l)` | Formatted address, a Google Maps directions link, and a map iframe `src` |
| `Content::testimonials($featuredOnly)` | Testimonials that have text |
| `Content::faqs('home')` | FAQs in a group from `FAQ_GROUPS` |
| `Content::formGroups()`, `Content::patientForms()` | Patient-form PDFs that have a file (§6) |
| `Content::videoList([...keys])` | Videos from `VIDEOS` that exist in the Media Library |

### 5.3 Fields on each record

- **Service** (`services` table):
  - `slug`, `title`, `menu_label`, `category`, `icon`, `image`, `excerpt`, `hero_text`
  - rich-text HTML: `what_is`, `how_it_works`, `what_to_expect`
  - one item per line: `conditions`, `benefits`
  - `faqs`: a JSON list of `{q, a}`
  - comma-separated slugs: `related`
  - comma-separated `BODY_AREAS` keys: `body_areas`
  - flags: `featured`, `coming_soon`, `show_in_menu`
  - `meta_title`, `meta_description`, `status`, `sort_order`, `updated_at`
- **Provider:**
  - `slug`, `name`, `credentials`, `title`, `type` (a `PROVIDER_TYPES` key)
  - `photo`, plus `photo_position`, the focal point to use as CSS `object-position`
  - comma-separated location slugs: `locations`
  - `short_bio`
  - rich text: `bio`, `education`, `experience`, `philosophy`
  - one item per line: `focus`
  - comma-separated service slugs: `services`
  - `meta_title`, `meta_description`
- **Page:** `slug`, `title`, `eyebrow`, `intro`, `content` (rich text), `image`, `template`, `show_cta`, `meta_title`, `meta_description`
- **Location:** `slug`, `name`, `address`, `city`, `state`, `zip`, `phone`, `fax`, `hours` (JSON; use `Content::hours()`), `map_embed`, `directions_url`, `image`, `description`, `latitude`, `longitude`
- **Testimonial:** `name`, `label`, `content`, `rating`, `featured`
- **FAQ:** `question`, `answer`, `grp`

### 5.4 Settings (Dashboard → Settings)

All settings are read with `setting('key')`. Empty means not set. Never print placeholder facts in its place; hide the element instead.

- **General:**
  - `site_name`, `site_short_name`, `tagline`, `brand_description`
  - `phone`, `sms_phone`, `email`
  - `notify_email` (where form emails go)
  - `communities`, `founded_year`, `cookie_notice`
- **Branding:** `logo`, `logo_light`, `favicon`, `color_primary`, `color_accent`, `color_highlight`. `layout.php` prints the colours as the CSS variables `--primary` and `--accent`.
- **Homepage:**
  - `hero_line1`, `hero_line2`, `hero_copy`, `hero_image`
  - `hero_badges` (one per line)
  - `about_image`, `testimonials_image`
  - `stats` (JSON list of `{value, suffix, label}`)
  - `featured_services`
  - `cta_headline`, `cta_copy`, `cta_small_print`
- **Forms & integrations:**
  - `ghl_appointment_embed`, `ghl_contact_embed`, `ghl_benefits_embed`, `ghl_webhook_url`
  - `chat_widget`, `ga_id`, `head_scripts`, `body_scripts`
- **Patient forms:** `form_new_patient_en`, `form_new_patient_es`, `form_accident_en`, `form_accident_es`
- **Social:** `social_facebook`, `social_x`, `social_instagram`
- **SEO:** `seo_title_suffix`, `seo_default_description`, `seo_og_image`, `seo_noindex`

**To add a setting**, for example a homepage section heading:

1. Add a default in `Settings::defaults()`.
2. Add a field to a group in `Resources::settings()`. Field types: `text`, `textarea`, `lines`, `image`, `file`, `color`, `toggle`, `code`, `relation`, `repeater`.
3. Read it in the template.

This keeps the homepage text editable without code.

### 5.5 SEO in views

Each page view should call:

```php
Seo::set([
    'title' => …,
    'description' => …,
    'canonical' => abs_url('…/'),
    'image' => $imagePath,
]);
```

Breadcrumbs are set as `Seo::$breadcrumbs = [['Home', ''], ['Label', 'path/']]`; the scaffold's `partials/page-head.php` does this. Extra JSON-LD goes through `Seo::addSchema([...])`. FAQs rendered with `partial('faq', ['faqs' => $faqs])` add FAQ schema automatically. `Seo::head()` in `layout.php` prints everything, so **keep that call**.

---

## 6. Media

- **Uploads:**
  - Uploaded files go to `uploads/YYYY/MM/`.
  - Content stores the path, for example `uploads/2026/10/front-desk.jpg`.
  - Output images with `img($path, $alt)` so the WebP version and stored dimensions are used.
  - Output other files with `media_url($path)`.
- **Auto-assign** (Media Library → Auto-assign) fills empty image fields by matching file names:
  - `{service-slug}.jpg` → that service's image
  - provider name → photo (for example `dr-jane-smith.jpg`)
  - `page-{slug}.jpg` → page image
  - `location-{slug}.jpg` → location image
  - `home-hero`, `home-about`, `home-testimonials`, `logo`, `logo-light`, `favicon`, `social-share`
  - Tell the practice to name photos this way.
- **Patient-form PDFs:**
  - Add entries to `Content::FORM_GROUPS`, each with a `file` name to look for in the library and an optional setting key.
  - A form with no matching file is hidden.
  - The scaffold appointment page lists them under `#patient-forms`.

---

## 7. Forms

Use `<?php partial('form', ['type' => 'appointment']); ?>`. The type is `appointment`, `contact` or `benefits`.

- If the matching GoHighLevel embed setting has a value, the partial prints that embed inside `<div class="form-embed">`. Style the wrapper only; the inside of a GoHighLevel iframe cannot be styled from the site. Its colours and fonts are set in GoHighLevel.
- Otherwise it prints the built-in form, which posts to `/api/form`:
  - Without JavaScript, the server redirects back with `?sent=1#form`.
  - If you submit with `fetch()` and send the `Accept: application/json` header, you get JSON back instead.
  - Keep the hidden fields `_form`, `_token`, `_page` and `website` (the honeypot) and the `consent` checkbox.
- Field definitions live in `Forms::fields()`. Do not collect date of birth or detailed medical information on general forms.

---

## 8. Content: seed versus migrations

- **Before the site is installed on the server:** put content in `app/seed/services.php` (the format is documented at the top of the file) and `app/seed/seed.php` (pages and redirects). The installer loads it once.
- **After install:** the seed never runs again. Deliver content as a **migration**:
  - Add a file `app/migrations/2026_10_20_001_add_services.php` that returns a closure.
  - The closure returns an array of log lines when done, or `false` to retry on the next deploy (up to 3 attempts).
  - The deploy runs pending migrations. Use `php app/cli/migrate.php --list` and `--rerun=<id>` locally.
  - Example:

```php
<?php
return function () {
    $now = now();
    if (!DB::val('SELECT COUNT(*) FROM services WHERE slug = ?', ['example-service'])) {
        DB::insert('services', [
            'slug' => 'example-service',
            'title' => 'Example Service',
            'category' => 'wellness',
            'excerpt' => '…',
            'hero_text' => '…',
            'what_is' => '<p>…</p>',
            'conditions' => "…",
            'how_it_works' => '',
            'benefits' => '',
            'what_to_expect' => '',
            'faqs' => '[]',
            'related' => '',
            'body_areas' => '',
            'image' => '',
            'icon' => 'activity',
            'menu_label' => '',
            'featured' => 0,
            'coming_soon' => 0,
            'show_in_menu' => 1,
            'needs_review' => 1,
            'sort_order' => 100,
            'status' => 'published',
            'meta_title' => '',
            'meta_description' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
    Settings::set('email', 'info@thefscd.org');
    return ['example-service added'];
};
```

Never edit the live database by hand, and never overwrite content the practice may have edited in the dashboard without checking first.

---

## 9. Deploy to SiteGround

### 9.1 One-time setup (the user does this; Claude cannot)

1. **SSH key.** In SiteGround Site Tools → Devs → SSH Keys Manager:
   - Create a key.
   - Note the **hostname** and **username**. The port is **18765**.
   - Copy the private key.
2. **GitHub secrets.** In the repository → Settings → Secrets and variables → Actions, add:
   - `SSH_HOST`
   - `SSH_USER`
   - `SSH_PRIVATE_KEY`
   - `SSH_PASSPHRASE` (only if the key has one)
   - `SSH_DIR` (optional; default `www/ahrons23.sg-host.com/public_html/`)
3. **Database.** In Site Tools → Site → MySQL, create a database and a user with all privileges on it. These details are typed only into the installer, **never into chat**.
4. **HTTPS.** In Site Tools → Security, add an SSL certificate for the subdomain and turn on HTTPS Enforce.

### 9.2 First deploy and install

1. Run Actions → **Deploy to SiteGround** with **first_deploy** ticked. The check stops if the folder holds a different app's `index.php`; empty the folder first, after making a backup.
2. Open `https://ahrons23.sg-host.com/install/`:
   - Choose MySQL with host `localhost` and enter the database details.
   - Create the admin account.
   - Keep **Hide the site from search engines** ON while on staging.
3. Sign in at `/admin/` and fill in **Settings → General / Branding** (name, phone, email, logo, colours).

### 9.3 Later deploys

Run the workflow without first_deploy. It:

- PHP-lints the code
- runs `npm run build`
- rsyncs only changed files
- runs migrations

It **never** uploads or deletes `app/config.php`, the database, sessions, logs or `uploads/`. Read the job log to confirm. SiteGround's bot protection may stop you loading the live site, so ask the user to check pages in a browser.

### 9.4 Going live on the real domain later

1. Point the real domain's `public_html` at this site. Either deploy to it (set `SSH_DIR`), or move the files and **copy `app/config.php` and `uploads/`** across. The site detects its domain automatically.
2. Add redirects for any old URL that changes.
3. Turn **Hide the site from search engines** OFF (Settings → SEO).

---

## 10. Things to keep in mind

- **Timezone:** `app/bootstrap.php` sets `UTC` until the organisation confirms its timezone.
- **No invented facts:** do not invent statistics, credentials, reviews, team members, hours, insurance acceptance or prices. Sections with no data are hidden by design; keep that behaviour in the new templates.
- **Escaping:** escape output with `e()`. Use `Html::clean()` for rich text. Print raw only the admin-only code settings: embeds, chat widget and scripts.
- **Assets:** run `npm run build` after editing CSS or JS, and commit the `.min` files. `layout.php` prefers them.
- **Self-contained dashboard:** the dashboard does not depend on `assets/`, so you can restructure the frontend assets freely.

## 11. Before each deploy

- `find . -name '*.php' -not -path './node_modules/*' -exec php -l {} \;` shows no errors.
- These pages return 200:
  - `/`, `/services/`, a service page
  - `/book-appointment/`, `/contact-us/`
  - the legal pages, `/sitemap/`, `/sitemap.xml`, `/robots.txt`
- `/admin/` works: sign in, edit a service, upload an image.
- A test contact form submission appears in **Form Inbox**.
- No horizontal scrolling at 360px, 768px or 1440px. Pages have no JavaScript errors.
