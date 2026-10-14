# FSCD Website Rebuild Brief — WordPress/Elementor to Custom PHP

**Organisation:** Foundation for Sustainable Community Development (FSCD)  
**Current site (reference):** https://ahrons18.sg-host.com (WordPress + Elementor)  
**New site (staging):** https://ahrons23.sg-host.com (SiteGround)  
**Repository:** the new GitHub repository attached to this session  
**Kit:** `fscd-site-kit.zip` (attached)

This brief is written for Claude Code. Read all of it before starting. It overrides your defaults, and where it conflicts with `BACKEND.md`, this brief wins.

---

## 1. The task in one paragraph

Rebuild the 14-page FSCD website so that every page **looks and behaves exactly like the current WordPress site**, but runs on the custom PHP backend and dashboard in the kit instead of WordPress. The pages were hand-written HTML inside Elementor "HTML" widgets. They have been extracted and prepared for you in `app/seed/fscd/`, and must be reproduced faithfully, not redesigned. The backend (dashboard, media library, form inbox, users, settings, SEO, redirects, installer, migrations and deploy) already exists and is documented in `BACKEND.md`. Your job is the glue:

- **Pages:** store each page in the CMS, render it exactly, and make it editable in the dashboard.
- **Header and footer:** share one copy across all pages.
- **Forms:** replace the third-party form service with the backend's forms.
- **Images:** move images into the Media Library.
- **Proof:** check, with automated screenshot comparison, that nothing changed visually.
- **Deploy:** deploy to ahrons23.sg-host.com.

---

## 2. What you have been given

| Item | Where |
|---|---|
| Backend and dashboard (PHP 8, MySQL/SQLite, no framework) | Kit root. See `BACKEND.md`. Unzip the kit and move `fscd-site/`'s contents, **including hidden files** (`.github/`, `.htaccess`, `.user.ini`, `.gitignore`), to the repository root. |
| The 14 pages, prepared | `app/seed/fscd/pages/*.html`. Read `app/seed/fscd/README.md` for exactly what was changed. |
| Images and video | `app/seed/fscd/media/`: the WordPress Media Library files plus the decoded embedded images |
| Page list, URLs and the gap list | `app/seed/fscd/manifest.json` |
| Files the user downloads separately | `missing-files.zip`, if attached. Put its contents in `app/seed/fscd/media/` (images) and `app/seed/fscd/eye-care/` (`fscd-eye.css`, `fscd-eye.js`). |
| Screenshots of the current site | `screenshots.zip`, if attached: full-page desktop and mobile captures of ahrons18 to use as the visual reference |

**Files the pages use that were not in the original upload** (`manifest.json` → `missing_files`). Check each one before building the affected page:

| File | Used by | Where it comes from |
|---|---|---|
| `wp-content/uploads/fscd/fscd-eye.css` | Eye Care | **Required.** The Eye Care page's whole stylesheet. |
| `wp-content/uploads/fscd/fscd-eye.js` | Eye Care | **Required.** The Eye Care page's script. |
| `Copy-of-Copy-of-Untitled-Design-1.png`, `Copy-of-Copy-of-Untitled-Design.png`, `Untitled-design-86.png`, `eye-scan.png` | Home | hot-linked from foundationforsustainablecommunitydevelopment.org/wp-content/uploads/2026/08/ |

If any of these are not attached, ask the user for them once, then carry on with everything else. Do not invent replacements. You probably cannot download them yourself: SiteGround's bot protection blocks automated requests, and you must not try to get around it.

**`Foundation-Logo-Icon.png` does not exist on the current server**, as the user confirmed. It is referenced only as a faint (4.5% opacity) decorative background in the footer CSS (`.fscd-footer::before`), so the live site shows nothing there. Leave the CSS reference exactly as it is. It renders nothing, the same as today. Do not create or substitute an image for it.

The WordPress XML export is **not needed**. Everything useful in it (page titles, slugs, URLs and template) is already in `manifest.json`.

---

## 3. Rules

1. **Exact replica.** Same markup, CSS, JavaScript, text, images, animations, fonts and behaviour at every screen width. Do not redesign, "improve", reformat or rewrite copy. Do not rename classes or IDs. Do not merge or "clean up" CSS across pages: different pages define the same class names differently, so merging page CSS will break layouts.
2. **The only intended differences:**
   - one shared header and one shared footer (§5)
   - forms that post to the backend instead of formsubmit.co (§7)
   - images served from the site's own Media Library (§6.4)
   - links that point to the new site
3. **Same URLs** as the current site (§4), including `/privacy-policy-2/`.
4. **Proof, not assumption.** Every page passes the visual comparison in §9 before you call it done.
5. **No invented content:** no new text, statistics, people, logos or claims.
6. **Security:**
   - Never commit `app/config.php`, databases, logs or uploads; the kit's `.gitignore` handles this.
   - Raw page HTML and code fields are editable only by the **Administrator** role (§8).
   - Do not bypass SiteGround's bot protection ("sgcaptcha").
7. **Search engines:** keep **Hide site from search engines** ON (staging).
8. **Git:** commit with clear messages, push to the branch this session tells you, and do not open pull requests unless asked.

---

## 4. Pages and URLs

| URL | Source file | Notes |
|---|---|---|
| `/` | `home.html` | Full HTML document. Convert it to a fragment (§6.2). Its `<title>` and meta description become the page's SEO fields. |
| `/about-us/` | `about-us.html` | |
| `/our-programs/` | `our-programs.html` | |
| `/eye-care/` | `eye-care.html` | Needs `fscd-eye.css` and `fscd-eye.js` (§2). Uses the Eyer2 MP4 video and the `fscd-eye-*` images. |
| `/clean-energy/` | `clean-energy.html` | Has a YouTube embed |
| `/global-benefits/` | `global-benefits.html` | |
| `/changing-lives/` | `changing-lives.html` | |
| `/strategic-partners/` | `strategic-partners.html` | |
| `/leadership/` | `leadership.html` | |
| `/dilip-limaye/` | `dilip-limaye.html` | |
| `/resources/` | `resources.html` | |
| `/contact/` | `contact.html` | Contact form with topic router and AJAX submit (§7) |
| `/privacy-policy-2/` | `privacy-policy-2.html` | Keep this exact slug. Also add a 301 from `/privacy-policy/`. |
| `/terms-of-service/` | `terms-of-service.html` | |

Also provide:

- **404 page:** shared header and footer, with a simple "Page not found" section styled to match. Reuse a legal page's existing styles; do not invent a new look.
- **`/sitemap.xml` and `/robots.txt`:** both are already built in.

**SEO titles:** WordPress showed `<Page title> – Foundation for Sustainable Community Development`; use that format. For the homepage, use the `<title>` inside `home.html`.

**Meta descriptions:**

- Home: use the description from `home.html`.
- Every other page: use a sentence taken verbatim from that page's own hero or intro text (no new wording), with `needs_review = 1`.

---

## 5. Shared header and footer

Each page carries its own pasted copy of the header and footer. They have drifted apart. This table shows which pages share an identical copy:

| Part | Variants found | Which pages share a copy |
|---|---|---|
| Header (`#fscd-master-shell-top`) | 11 | Leadership = Dilip Limaye; Privacy = Resources = Terms; every other page is unique |
| Footer (`#fscd-master-shell-bottom`) | 2 | Home, Clean Energy, Dilip Limaye, Eye Care, Global Benefits, Leadership, Privacy, Resources, Terms share one; About, Changing Lives, Contact, Our Programs, Strategic Partners share the other |

The differences are small: the "active" menu item, some full stops in menu descriptions, and a few menu wording changes (for example "Vision Care Program" versus "Vision Care", "diagnostic pathways" versus "referral pathways"), plus footer heading punctuation.

**What to do:**

1. Use the **Home page's header** and the **majority footer** (Home's) as the canonical versions.
2. Before going further, show the user a short list of the text differences you are normalising, so they can pick different wording if they want.
3. Store the canonical header and footer **markup** once, as dashboard settings `site_header_html` and `site_footer_html`, under **Settings → Header & Footer**, Administrator only. They are code fields that the renderer prints into every page.
4. Mark the active menu item on the server, using the `fscd-active` class exactly as each original page did. This is the mapping found in the originals:

   | Active item | Pages |
   |---|---|
   | Home | Home |
   | About | About Us, Leadership, Dilip Limaye |
   | Programs | Our Programs, Eye Care, Clean Energy |
   | Impact | Global Benefits, Changing Lives |
   | Partners | Strategic Partners |
   | Contact | Contact |
   | none | Resources, Privacy Policy, Terms of Service |

   Keep this as a small slug-to-menu-item map, so new pages can be assigned in code or settings.
5. In each page's stored HTML, replace the two master-shell blocks with the tokens `{{header}}` and `{{footer}}`. This keeps the DOM order exactly as it was: page styles, then header, then content, then footer, then scripts.
6. **Leave each page's header and footer CSS and JS in that page** (step one). Every page then keeps styling the header exactly as it did, and the visual comparison stays exact. Only after all 14 pages pass §9, consider an optional second step: move the header and footer CSS and JS into one shared file. Do this only if the comparison still passes for every page.

---

## 6. Backend changes

The backend is in `BACKEND.md`. Make these changes; keep everything else as it is.

### 6.1 HTML page template

- **Template:** add a page template `html` and a view `app/views/pages/html.php`, rendered through a new **minimal layout** (`app/views/layout-html.php`). Do **not** load the kit's scaffold CSS or JS on these pages; it would interfere with the original styles. The layout contains only:
  - `<!doctype html>`, `<html lang="en">`, and `<head>` with `Seo::head()`
  - favicon (only if one is set in Settings → Branding; the current site has none of its own), Google Analytics and `head_scripts`
  - `<body>` holding the rendered page HTML
  - `chat_widget` and `body_scripts`
- **Body styles:** match the original page context. Elementor Canvas gave a plain `<body>`; check `body { margin: 0 }` and any inherited styles against the screenshots or originals.
- **Storage:** the `pages.content` column (already LONGTEXT) stores the page fragment.
- **Tokens:** render the fragment raw (trusted, Administrator-only), replacing these tokens:
  - `{{header}}` and `{{footer}}` → the settings, with the active menu item set (§5)
  - `{{media:<file name>}}` → `media_url()` of the Media Library item with that original file name, so replacing a file in the Media Library updates every page that uses it. Build the name-to-path map once per request.
  - `{{form_token}}` → `Forms::token()` (§7)
- **Unknown tokens:** leave them visible, and add a dashboard health warning for any page that contains one.
- **Router:** add `html` to the `$templates` list in `index.php`. Make the homepage use the page with slug `home`, rendered with this template. Keep the old `pages/home.php` only as a fallback if no such page exists.

### 6.2 Importing the pages

Write the import as part of the seed (`app/seed/seed.php`), so `/install/` creates the site complete. Also add a re-runnable CLI script `app/cli/import-fscd.php`, for local resets and for adding missing files later. For each manifest entry:

1. Read `app/seed/fscd/pages/<slug>.html`.
2. **For `home.html` only:**
   - Keep everything inside `<head>` except `<meta charset>`, `<meta viewport>`, `<title>` and `<meta name="description">`; the layout outputs those.
   - Keep everything inside `<body>`, in order. The result is a fragment like the others.
   - Save the title and description to the page's SEO fields.
3. Replace the header and footer master-shell blocks with `{{header}}` and `{{footer}}` (§5).
4. **For the Eye Care page:** replace the `<link … fscd-eye.css>` and `<script … fscd-eye.js>` tags with the files' contents inline (`<style>` and `<script defer>`) **at the same positions**. Inside those files, turn any `/wp-content/uploads/...` file references into `{{media:…}}` tokens. If the files are missing, import the page anyway and flag it in the dashboard health list.
5. Rewrite the two forms (§7).
6. Insert the page:
   - `template = 'html'`, `is_system = 1`, `status = 'published'`
   - `sort_order` in menu order
   - `meta_title` and `meta_description` per §4
7. **Media import:** import every file in `app/seed/fscd/media/` into the Media Library through the backend's own pipeline, so each gets a database row, WebP version, thumbnail and dimensions. `Media::upload()` expects an uploaded file, so add a small `Media::importLocal($path, $folder = 'fscd')` that reuses the same processing. Use the file name, without extension and humanised, as the alt text.

After install, all later content changes go out as **migrations** in `app/migrations/`; the seed never runs again.

### 6.3 Dashboard

- **Pages editor, for template `html`:** show a large **code editor** for the page HTML instead of the rich-text editor.
  - A monospace textarea is acceptable. A CodeMirror build loaded from cdnjs or jsDelivr is better: HTML mode, line numbers and search.
  - Add a **Preview** button: drafts already preview with `?preview=1`.
  - Add a short help note: "Paste only this page's content. The header and footer are shared — use `{{header}}` and `{{footer}}`. Use `{{media:file-name.jpg}}` or a Media Library URL for images."
- **Revisions:** add a `page_revisions` table: page_id, content, user_id, created_at. On each save of `content`, store the previous version. Keep the latest 30 per page, and add a **Revisions** panel with "Restore". Raw HTML editing needs an undo.
- **Safety net on save:** if pasted HTML still contains a full header or footer master-shell block, replace it with the token automatically and show a notice.
- **Hide unused modules:** remove Treatments, Providers, Testimonials, FAQs and Locations from the sidebar, the search, the dashboard KPIs and the health checks. Hide them; do not delete the code. A `hidden` flag in `Resources` is enough. Also hide irrelevant Settings groups: Homepage stats and featured treatments, Patient Forms, and Videos. Keep:
  - Pages, Media Library, Form Inbox
  - Users & Roles, Settings, Redirects, Activity Log
- **Settings → General:**
  - site name and short name, `email` = `info@thefscd.org`
  - `notify_email` = `info@thefscd.org` (form notifications). Make the installer keep a value the seed already set, instead of overwriting it with the admin's email.
  - social links, if the pages contain any
- **Branding:** primary `#062b4b`, accent `#4c8f2f` (already the defaults). The dashboard picks these up automatically.

### 6.4 Images hot-linked from other websites

`manifest.json` → `third_party_images` lists them:

- **Unsplash (41) and Pexels (12):** their licences allow self-hosting. If your network allows, download them into the Media Library and swap the URLs for `{{media:…}}` tokens, so the site no longer depends on other servers. If downloads are blocked, keep the original URLs and list them in your report.
- **Wikimedia (1):** keep the URL unless you can confirm the file's licence.
- **phelcom/rocketcdn (3), niet.co.in (1) and squarespace-cdn (1):** these belong to other organisations. **Keep them exactly as they are**, as on the current site, and tell the user they should confirm permission or replace them.
- **Keep as they are:** Google Fonts (DM Sans, Oswald), Font Awesome from cdnjs, and the YouTube embed. The pages use two Font Awesome versions (6.5.2 and 6.6.0); leave each page's link as it is.

---

## 7. Forms (replace formsubmit.co)

There are two forms. Both currently send to `formsubmit.co/info@thefscd.org`.

**Home** (`<form class="fscd-form-grid" action="https://formsubmit.co/…">`) is a normal POST.

| | |
|---|---|
| Fields | `First Name`, `Last Name`, `Email`, `Organization`, `Interest`, `Message` |
| Hidden | `_subject` ("New FSCD Website Inquiry"), `_template`, `_captcha` |

**Contact** (`#fscd-c-form`) is submitted by JavaScript with `fetch('https://formsubmit.co/ajax/info@thefscd.org', …)`. A topic "router" changes the hidden `Topic` and `_subject` values and shows or hides fields. On success, the page shows a status message in `#fscd-c-form-status`, and it also handles `?submitted=1`.

| | |
|---|---|
| Fields | `name`, `email`, `Organization`, `Role / Title`, `Location`, `Country / Location`, `Role / Community Profile`, `Community Need`, `Subject`, `Message` |
| Hidden | `Topic`, `_subject`, `_template`, `_captcha`, `_next`, `_url`, `_honey` |

**Do this:**

1. **Form types:** in `Forms::fields()` add `home_inquiry` and `fscd_contact`, with the fields above. Add their labels to `Forms::TYPES`.
2. **Field names:** PHP turns spaces and dots in POST field names into underscores. Give the inputs **snake_case `name` attributes** (`first_name`, `last_name`, `organization`, `role_title`, `country_location`, …). Leave the visible labels and all markup untouched. Update any page JavaScript that reads those names.
3. **Endpoint and spam protection:**
   - Point both forms at `/api/form`.
   - Add the hidden fields `_form` (the type), `_token` (`{{form_token}}`) and `_page`.
   - Map the honeypot: rename `_honey` to `website`, the backend's honeypot field.
   - Remove `_template`, `_captcha`, `_url` and `_next`.
   - The backend rejects submissions made less than 2 seconds after page load. That is fine for people; keep it.
4. **Consent:** the original forms have no consent checkbox. Make the consent check **optional per form type**, off for these two, so the markup stays identical.
5. **Email and inbox:**
   - Send the per-submission subject to `notify_email`: the `_subject` / topic value, for example "FSCD Website: General Inquiry".
   - Store `Topic` in the submission data.
   - Fill the submission's `name` from `name`, or from `first_name` + `last_name`.
6. **Contact AJAX:** change the `fetch` URL to `/api/form` and send `Accept: application/json`. The backend returns `{"ok":true,"message":"…"}` on success and `{"ok":false,"error":"…"}` with HTTP 4xx on failure. Adapt only the response handling, so the same success and error UI appears as now.
7. **Home (no JavaScript):** after success, redirect back to `/?submitted=1#contact`, which is the Home form's section ID; check it in `home.html`.
   - Add a success redirect target per form type. Today the redirect anchor is hard-coded to `#form`, and it must only allow site-relative paths.
   - If the original Home page shows no success message, show a small, unobtrusive one in the existing form status style. This is the one addition allowed, because formsubmit used to show its own thank-you page. Ask the user if unsure.
8. **No-JavaScript error page:** `Forms::handle()` shows an HTML error page that says "call <phone>". FSCD has no phone number in Settings, so offer the email address (`setting('email')`) when the phone is empty.
9. **Test:** both forms appear in **Form Inbox**, and the email reaches `notify_email`. Email is sent with PHP `mail()` on SiteGround; mention SPF/DKIM (or SMTP) in your report if it lands in spam.

---

## 8. Security for raw HTML

The `html` template outputs stored HTML and JavaScript unfiltered. This is what Elementor's HTML widget did, and it is required for fidelity. To compensate:

- **Who can edit:**
  - Only the **Administrator** role can create or edit `html` pages, the header and footer settings, and the code settings. Enforce it in `admin/api.php`, not just the UI.
  - Editors and Authors can still upload media and read the inbox.
- **What is never accepted from public input:** do not accept tokens or raw HTML from public form input; `strip_tags` already applies there.
- **Audit:** keep the activity log entries for page saves; they already exist.

---

## 9. Proving the pages are identical (required)

Build a repeatable check, for example `scripts/visual-check.js` using Playwright. Chromium is usually pre-installed; if a different version is needed, set `executablePath`.

1. **Reference:** render each original `app/seed/fscd/pages/<slug>.html` locally, with `{{media:x}}` resolved to `app/seed/fscd/media/x`. Wrap fragments in `<!doctype html><html><head><meta charset="utf-8"></head><body>` with `body{margin:0}`. If `screenshots.zip` was supplied, also compare against those images by eye; they show the real site, including anything WordPress added around the widget.
2. **Candidate:** the same page on your local server (`php -S 127.0.0.1:8080 index.php` after a SQLite install).
3. **Capture at 1440×900 and 390×844**, full page:
   - Block network requests other than fonts and cdnjs in **both** captures, or allow them in both. Treat both the same.
   - Inject `*{animation:none!important;transition:none!important}`.
   - Force elements revealed on scroll to be visible: add the pages' own "visible" classes, or set `opacity:1` on elements whose computed opacity is 0.
   - Wait for fonts: `document.fonts.ready`.
4. **Compare pixel by pixel** (for example, pixelmatch or Pillow). Report the rows that differ.
   - **Expected:** time-based animations, such as rotating headlines, the animated diagram and the marquee, differ between captures. Mask them, or confirm visually that only the animated element differs.
   - **Allowed:** the normalised header and footer text (§5).
   - **Not allowed:** any other difference. Fix it.
5. Save the report and side-by-side images for any page with differences. Include a summary table (page × width × result) in your final message.

**Also check:**

- **Behaviour:** the mobile menu, dropdowns, sticky header, counters, tabs and sliders work, and the contact topic router changes fields. No console errors.
- **Links:** every internal link returns 200, and no link still points to `ahrons18.sg-host.com` or `formsubmit.co`. Use a grep and a crawl.

---

## 10. Deploy

Follow `BACKEND.md` §9. `.github/workflows/deploy.yml` deploys to `www/ahrons23.sg-host.com/public_html/` by default.

1. **Secrets:** the user adds the GitHub secrets `SSH_HOST`, `SSH_USER`, `SSH_PRIVATE_KEY` and optionally `SSH_PASSPHRASE`. Ask them to confirm.
2. **First deploy:** run the workflow with **first_deploy** ticked. If `public_html` holds another site, such as a WordPress install or SiteGround's default page, the check stops. Ask the user to empty the folder first, after making a backup.
3. **Install:** the user opens `https://ahrons23.sg-host.com/install/`, chooses MySQL (host `localhost`), creates the admin account and keeps "Hide from search engines" ON. **Database passwords are typed only into the installer, never into chat.**
4. **Later deploys:** without first_deploy. They run migrations automatically.
5. **Checking:** you may not be able to load the live site because of SiteGround's bot protection. Read the deploy log, then ask the user to check the pages in their browser.

`app/seed/fscd/` is about 14 MB and is deployed (it sits under `app/`, which `.htaccess` blocks from the web). The installer needs it on the server.

---

## 11. Order of work and reporting

1. Set up the repository from the kit. Check which missing files and screenshots were provided, and ask for any missing ones once.
2. Backend changes (§6.1, §6.3, §8), then the import (§6.2) and the forms (§7).
3. Header and footer (§5): show the user the text differences before finalising.
4. Visual check (§9) until every page passes.
5. Self-host the stock images if possible (§6.4), then re-run §9.
6. Deploy (§10). The user installs and checks.
7. **Final report:**
   - what was built
   - the visual-check table
   - any third-party images kept, and why
   - files still missing, if any
   - how to edit a page in the dashboard
   - anything the user must do: email deliverability, timezone in `app/bootstrap.php` (currently UTC), and any wording choices for the header and footer

Keep progress messages short.
