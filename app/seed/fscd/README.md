# FSCD source pages (prepared from the WordPress/Elementor site)

These are the 14 published pages of https://ahrons18.sg-host.com. Each was built as one Elementor HTML widget on the "Elementor Canvas" template, which has no theme header or footer. They were exported as raw HTML, then prepared like this:

| Change | Detail |
|---|---|
| Embedded images | `data:image/...;base64` images were decoded into real files in `media/`, with identical images merged. Each was replaced by a token, `{{media:<file>}}`. |
| Media Library files | References to `.../wp-content/uploads/...` files that exist in `media/` became `{{media:<file>}}`. The Eyer2 video's long WordPress file name maps to `Meet-the-Eyer2-The-ultimate-all-in-one-portable-device.mp4`. |
| Internal links | `https://ahrons18.sg-host.com/<slug>/` became root-relative `/<slug>/`. |
| Everything else | Unchanged, byte for byte: markup, CSS, JavaScript, fonts, third-party image URLs, YouTube embed and forms. |

Rendering each page with `{{media:x}}` resolved to `media/x` gives the same result, pixel for pixel, as the original HTML. This was verified with full-page screenshots; the only differences were time-based animations.

## Files

- `pages/<slug>.html`: one per page; the file name is the WordPress slug.
  - `home.html` is a **full HTML document** (`<!DOCTYPE html>…`).
  - The other 13 are **fragments**: `<link>` and `<style>` blocks, then markup, then `<script>` blocks.
  - Every page contains its own copy of the site header (`<div class="fscd-master-shell" id="fscd-master-shell-top">…</div>`) and footer (`<div class="fscd-master-shell" id="fscd-master-shell-bottom">…</div>`). The copies differ slightly between pages; see BRIEF.md §5.
- `media/`: the Media Library files you supplied plus the decoded embedded images.
- `manifest.json`: contains:
  - `pages`: slug, URL, title and file for each page
  - `media` and `embedded_images`
  - `missing_files`: URLs the pages use whose files were **not** supplied (see BRIEF.md §2)
  - `third_party_images`: images hot-linked from other websites

## Page URLs (WordPress slugs)

`/` (home), `/about-us/`, `/our-programs/`, `/eye-care/`, `/clean-energy/`, `/global-benefits/`, `/changing-lives/`, `/strategic-partners/`, `/leadership/`, `/dilip-limaye/`, `/resources/`, `/contact/`, `/privacy-policy-2/`, `/terms-of-service/`
