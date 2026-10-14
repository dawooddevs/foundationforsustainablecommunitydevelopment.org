#!/usr/bin/env node
/**
 * Visual regression check (BRIEF.md §9): each original page in app/seed/fscd/pages/ versus the same
 * page served by the CMS, full page, at 1440×900 and 390×844, compared pixel by pixel.
 *
 *   php -S 127.0.0.1:8080 index.php &        (after a local SQLite install)
 *   node scripts/visual-check.js [slug …] [--base=http://127.0.0.1:8080] [--out=scripts/visual-report]
 *
 * Reference: the seed file rendered as the old site served it — fragments wrapped in a bare document
 * with body{margin:0}; {{media:x}} and the old site's /wp-content/uploads/… URLs served from
 * app/seed/fscd/media/ and app/seed/fscd/eye-care/.
 * Both captures: same network rules (only local files and Google Fonts load), animations and
 * transitions off, a fixed fake clock (timers, rotating headlines and counters advance identically),
 * every scroll-reveal triggered by scrolling through the page, fonts loaded.
 * Output: report.md + report.json, and side-by-side/diff PNGs for pages that differ.
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const { PNG } = require('pngjs');
const pixelmatch = require('pixelmatch');

const ROOT = path.resolve(__dirname, '..');
const SEED = path.join(ROOT, 'app/seed/fscd');
const args = process.argv.slice(2);
const opt = (k, d) => { const a = args.find((x) => x.startsWith('--' + k + '=')); return a ? a.split('=').slice(1).join('=') : d; };
const BASE = opt('base', 'http://127.0.0.1:8080').replace(/\/$/, '');
const OUT = path.resolve(ROOT, opt('out', 'scripts/visual-report'));
const REF_ORIGIN = 'http://reference.local';
const manifest = JSON.parse(fs.readFileSync(path.join(SEED, 'manifest.json'), 'utf8'));
const only = args.filter((a) => !a.startsWith('--'));
const pages = manifest.pages.filter((p) => !only.length || only.includes(p.slug));
const WIDTHS = [{ name: 'desktop', width: 1440, height: 900 }, { name: 'mobile', width: 390, height: 844 }];
const ALLOWED_HOSTS = [/^fonts\.googleapis\.com$/, /^fonts\.gstatic\.com$/];
const MIME = { '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.webp': 'image/webp', '.mp4': 'video/mp4', '.css': 'text/css', '.js': 'application/javascript', '.svg': 'image/svg+xml' };
const FREEZE_CSS = '*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important}html{scroll-behavior:auto!important}';

/** Offset/length of a master-shell block (nested divs; script/style/comment contents skipped). */
function findBlock(html, id) {
  const open = new RegExp('<div\\b[^>]*\\bid=["\']' + id + '["\'][^>]*>', 'i').exec(html);
  if (!open) return null;
  const re = /(<!--[\s\S]*?-->|<(script|style)\b[^>]*>[\s\S]*?<\/\2\s*>)|<(\/?)div\b[^>]*>/gi;
  re.lastIndex = open.index;
  let depth = 0, m;
  while ((m = re.exec(html))) {
    if (m[1]) continue;
    depth += m[3] === '/' ? -1 : 1;
    if (depth === 0) return [open.index, m.index + m[0].length - open.index];
  }
  return null;
}

/** The original page with the site's canonical header/footer swapped in (page-specific <style>/<script> kept). */
function normalise(html, shell) {
  for (const [id, key] of [['fscd-master-shell-top', 'header'], ['fscd-master-shell-bottom', 'footer']]) {
    const b = findBlock(html, id);
    if (!b) continue;
    const block = html.substr(b[0], b[1]);
    const keep = (block.match(/<(style|script)\b[^>]*>[\s\S]*?<\/\1\s*>/gi) || []).join('\n');
    html = html.slice(0, b[0]) + (keep ? keep + '\n' : '') + shell[key] + html.slice(b[0] + b[1]);
  }
  return html;
}

function referenceHtml(p, shell) {
  let html = fs.readFileSync(path.join(SEED, p.file), 'utf8');
  if (shell) html = normalise(html, shell);
  html = html.replace(/\{\{media:([^}]+)\}\}/g, (m, f) => REF_ORIGIN + '/media/' + encodeURIComponent(f.trim()));
  if (p.full_document) return html;
  return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>body{margin:0}</style></head><body>' + html + '</body></html>';
}

/** Serve a seed file for a URL the old site would have served (media and the Eye Care CSS/JS). */
function seedFileFor(url) {
  const u = new URL(url);
  const name = decodeURIComponent(u.pathname.split('/').pop() || '');
  if (u.origin === REF_ORIGIN && u.pathname.startsWith('/media/')) return path.join(SEED, 'media', name);
  if (/\/wp-content\/uploads\//.test(u.pathname)) {
    for (const dir of ['media', 'eye-care']) {
      const f = path.join(SEED, dir, name);
      if (fs.existsSync(f)) return f;
    }
  }
  return null;
}

async function route(page, refHtml) {
  await page.route('**/*', async (r) => {
    const url = r.request().url();
    const u = new URL(url);
    if (refHtml && u.origin === REF_ORIGIN && (u.pathname === '/' || u.pathname === '')) {
      return r.fulfill({ status: 200, contentType: 'text/html; charset=utf-8', body: refHtml });
    }
    if (refHtml) {
      const f = seedFileFor(url);
      if (f && fs.existsSync(f)) return r.fulfill({ status: 200, contentType: MIME[path.extname(f).toLowerCase()] || 'application/octet-stream', body: fs.readFileSync(f) });
      if (u.origin === REF_ORIGIN || /\/wp-content\//.test(u.pathname)) return r.fulfill({ status: 404, body: '' });
    } else if (url.startsWith(BASE)) {
      return r.continue();
    }
    if (ALLOWED_HOSTS.some((h) => h.test(u.hostname))) return r.continue();
    return r.abort();
  });
}

async function capture(browser, url, refHtml, vp, errors) {
  const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height }, deviceScaleFactor: 1, reducedMotion: 'no-preference' });
  const page = await ctx.newPage();
  page.on('pageerror', (e) => errors.push(String(e.message || e)));
  page.on('console', (m) => { if (m.type() === 'error' && !/Failed to load resource|ERR_FAILED|net::/.test(m.text())) errors.push(m.text()); });
  await page.clock.install({ time: new Date('2026-10-01T12:00:00Z') });
  await route(page, refHtml);
  await page.goto(url, { waitUntil: 'load', timeout: 90000 });
  await page.addStyleTag({ content: FREEZE_CSS });
  await page.evaluate(() => document.fonts.ready);
  // Trigger scroll-reveal observers, then come back to the top
  const h = await page.evaluate(() => document.documentElement.scrollHeight);
  for (let y = 0; y < h; y += Math.round(vp.height * 0.6)) {
    await page.evaluate((yy) => window.scrollTo(0, yy), y);
    await page.waitForTimeout(60); // a real frame, so IntersectionObservers deliver
    await page.clock.runFor(120);
  }
  await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight));
  await page.waitForTimeout(100);
  await page.clock.runFor(500);
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.clock.runFor(8000);
  await page.evaluate(() => document.fonts.ready);
  await page.waitForTimeout(300);
  const buf = await page.screenshot({ fullPage: true, animations: 'disabled', timeout: 120000 });
  const boxes = await page.evaluate(() => ['fscd-master-shell-top', 'fscd-master-shell-bottom'].map((id) => {
    const el = document.getElementById(id);
    if (!el) return null;
    const r = el.getBoundingClientRect();
    return [Math.floor(r.top + window.scrollY), Math.ceil(r.bottom + window.scrollY)];
  }).filter(Boolean));
  await ctx.close();
  const png = PNG.sync.read(buf);
  png.shellBoxes = boxes;
  return png;
}

function crop(png, w, h) {
  const out = new PNG({ width: w, height: h });
  PNG.bitblt(png, out, 0, 0, w, h, 0, 0);
  return out;
}

/** Row ranges that contain differing pixels, e.g. "1200–1260, 3400–3410". */
function rowRanges(diff, w, h) {
  const rows = [];
  for (let y = 0; y < h; y++) {
    let bad = false;
    for (let x = 0; x < w && !bad; x++) {
      const i = (y * w + x) * 4;
      if (diff[i] === 255 && diff[i + 1] === 0 && diff[i + 2] === 0) bad = true;
    }
    if (bad) {
      const last = rows[rows.length - 1];
      if (last && y - last[1] <= 8) last[1] = y; else rows.push([y, y]);
    }
  }
  return rows;
}

function compare(ref, cand) {
  const w = Math.min(ref.width, cand.width), h = Math.min(ref.height, cand.height);
  const diff = new PNG({ width: w, height: h });
  const n = pixelmatch(crop(ref, w, h).data, crop(cand, w, h).data, diff.data, w, h, { threshold: 0.1, includeAA: false, alpha: 0.2 });
  return { n, diff, rows: n ? rowRanges(diff.data, w, h) : [], sizeSame: ref.width === cand.width && ref.height === cand.height };
}

function sideBySide(a, b, d) {
  const w = a.width + b.width + d.width + 40, h = Math.max(a.height, b.height, d.height);
  const out = new PNG({ width: w, height: h });
  out.data.fill(255);
  PNG.bitblt(a, out, 0, 0, a.width, a.height, 0, 0);
  PNG.bitblt(b, out, 0, 0, b.width, b.height, a.width + 20, 0);
  PNG.bitblt(d, out, 0, 0, d.width, d.height, a.width + b.width + 40, 0);
  return out;
}

function loadShells() {
  const { execFileSync } = require('child_process');
  return JSON.parse(execFileSync('php', [path.join(__dirname, 'shells.php')], { cwd: ROOT, maxBuffer: 64 << 20 }).toString());
}

module.exports = { manifest, referenceHtml, route, compare, sideBySide, loadShells, REF_ORIGIN, BASE, ROOT, FREEZE_CSS };
if (require.main === module) (async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const shells = loadShells();
  const browser = await chromium.launch({ executablePath: process.env.CHROMIUM_PATH || undefined });
  const results = [];
  for (const p of pages) {
    for (const vp of WIDTHS) {
      const errRef = [], errCand = [];
      let cand = await capture(browser, BASE + p.url, null, vp, errCand);
      let retries = 0;
      for (let attempt = 0; attempt < 2; attempt++) {
        // 1. Strict: the untouched original. Differences may only be in the header/footer (§5 normalisation).
        // A differing capture is taken once more (rendering races such as late glyph rasterisation);
        // the retry count is reported so a flaky page is visible.
        let ref = await capture(browser, REF_ORIGIN + '/', referenceHtml(p, null), vp, errRef);
        let strict = compare(ref, cand);
        const outsideOf = (st, rf) => { const boxes = cand.shellBoxes.concat(rf.shellBoxes); return st.rows.filter(([y0, y1]) => !boxes.some(([t, b]) => y0 >= t - 2 && y1 <= b + 2)); };
        let outside = outsideOf(strict, ref);
        if (outside.length || !strict.sizeSame) {
          retries++;
          ref = await capture(browser, REF_ORIGIN + '/', referenceHtml(p, null), vp, []);
          strict = compare(ref, cand);
          outside = outsideOf(strict, ref);
        }
        // 2. Normalised: the original with the canonical header/footer swapped in. Must be identical.
        let norm = compare(await capture(browser, REF_ORIGIN + '/', referenceHtml(p, shells[p.slug]), vp, []), cand);
        if (norm.n || !norm.sizeSame) {
          retries++;
          norm = compare(await capture(browser, REF_ORIGIN + '/', referenceHtml(p, shells[p.slug]), vp, []), cand);
        }
        if (attempt === 0 && (norm.n || !norm.sizeSame || outside.length)) {
          // Still different: the candidate capture itself may be the odd one out; take it once more
          retries++;
          cand = await capture(browser, BASE + p.url, null, vp, []);
          continue;
        }
        const fmt = (rows) => rows.map((x) => x[0] + '–' + x[1]);
        const r = {
          slug: p.slug, viewport: vp.name, refSize: [ref.width, ref.height], candSize: [cand.width, cand.height],
          strictPixels: strict.n, strictRows: fmt(strict.rows), strictOutsideShell: fmt(outside),
          normPixels: norm.n, normRows: fmt(norm.rows), normSizeSame: norm.sizeSame,
          pass: norm.n === 0 && norm.sizeSame && outside.length === 0 && strict.sizeSame, retries, refErrors: errRef, candErrors: errCand,
        };
        const tag = p.slug + '-' + vp.name;
        for (const f of fs.readdirSync(OUT)) if (f.startsWith(tag + '-')) fs.unlinkSync(path.join(OUT, f));
        if (strict.n) fs.writeFileSync(path.join(OUT, tag + '-strict-side-by-side.png'), PNG.sync.write(sideBySide(ref, cand, strict.diff)));
        if (norm.n) fs.writeFileSync(path.join(OUT, tag + '-normalised-side-by-side.png'), PNG.sync.write(sideBySide(ref, cand, norm.diff)));
        console.log((r.pass ? 'PASS ' : 'FAIL ') + tag.padEnd(30) + ' normalised ' + norm.n + ' px' + (norm.n ? ' rows ' + r.normRows.slice(0, 6).join(', ') : '') + ' | strict ' + strict.n + ' px' + (strict.n ? (outside.length ? ' OUTSIDE shell: ' + r.strictOutsideShell.slice(0, 6).join(', ') : ' (all in header/footer)') : '') + (strict.sizeSame ? '' : ' size ' + ref.width + 'x' + ref.height + ' vs ' + cand.width + 'x' + cand.height) + (retries ? ' | re-captured ' + retries + 'x' : '') + (errCand.length ? ' | JS errors: ' + errCand.length : ''));
        results.push(r);
        break;
      }
    }
  }
  await browser.close();
  // Re-running some pages updates their rows in an existing report
  const prevFile = path.join(OUT, 'report.json');
  if (only.length && fs.existsSync(prevFile)) {
    const prev = JSON.parse(fs.readFileSync(prevFile, 'utf8')).filter((x) => !only.includes(x.slug));
    results.splice(0, 0, ...prev);
    const order = manifest.pages.map((x) => x.slug);
    results.sort((a, b) => order.indexOf(a.slug) - order.indexOf(b.slug) || (a.viewport === 'desktop' ? -1 : 1));
  }
  fs.writeFileSync(prevFile, JSON.stringify(results, null, 2));
  const md = ['# Visual check', '', 'Generated ' + new Date().toISOString() + ' against ' + BASE + '.', '',
    '- **Normalised**: the original page with the canonical header/footer swapped in, versus the CMS page. Must be 0.',
    '- **Strict**: the untouched original versus the CMS page. Differences are allowed only inside the header/footer (BRIEF §5).', '',
    '| Page | Width | Result | Normalised (px) | Strict (px) | Strict rows outside header/footer | Re-captures | JS errors |', '|---|---|---|---|---|---|---|---|']
    .concat(results.map((r) => '| ' + r.slug + ' | ' + r.viewport + ' | ' + (r.pass ? 'PASS' : 'FAIL') + ' | ' + r.normPixels + (r.normSizeSame ? '' : ' (size differs)') + ' | ' + r.strictPixels + (r.refSize.join() === r.candSize.join() ? '' : ' (size ' + r.refSize.join('×') + ' vs ' + r.candSize.join('×') + ')') + ' | ' + (r.strictOutsideShell.join(', ') || 'none') + ' | ' + r.retries + ' | ' + r.candErrors.length + ' |'));
  fs.writeFileSync(path.join(OUT, 'report.md'), md.join('\n') + '\n');
  console.log('\nReport: ' + path.relative(ROOT, path.join(OUT, 'report.md')));
})().catch((e) => { console.error(e); process.exit(1); });
