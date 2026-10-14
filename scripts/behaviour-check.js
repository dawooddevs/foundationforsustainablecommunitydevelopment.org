#!/usr/bin/env node
/**
 * Behaviour check (BRIEF.md §9): for every page, the same interactions on the original (with the
 * canonical header/footer swapped in) and on the CMS page, comparing the resulting state and pixels:
 *   desktop  mega menus open on hover (About, Programs, Partners), sticky header compacts on scroll
 *   mobile   menu toggle opens the navigation, a mega section expands inside it
 *   contact  the topic router updates the hidden Topic, the badge, the subject and the mail subject
 * plus uncaught JavaScript errors on the CMS page.
 *
 *   node scripts/behaviour-check.js [slug …] [--base=http://127.0.0.1:8080]
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const { PNG } = require('pngjs');
const V = require('./visual-check.js');

const only = process.argv.slice(2).filter((a) => !a.startsWith('--'));
const OUT = path.join(V.ROOT, 'scripts/visual-report');

async function open(browser, url, html, vp, errors) {
  const ctx = await browser.newContext({ viewport: vp, deviceScaleFactor: 1 });
  const page = await ctx.newPage();
  page.on('pageerror', (e) => errors.push(String(e.message || e)));
  await page.clock.install({ time: new Date('2026-10-01T12:00:00Z') });
  await V.route(page, html);
  await page.goto(url, { waitUntil: 'load', timeout: 90000 });
  await page.addStyleTag({ content: V.FREEZE_CSS });
  await page.evaluate(() => document.fonts.ready);
  await page.clock.runFor(1000);
  return { ctx, page };
}

const STEPS = {
  desktop: [
    ['mega About', async (pg) => { await pg.hover('#fscd-master-shell-top .fscd-has-mega:nth-of-type(2) .fscd-nav-trigger'); }],
    ['mega Programs', async (pg) => { await pg.hover('#fscd-master-shell-top .fscd-has-mega:nth-of-type(3) .fscd-nav-trigger'); }],
    ['mega Partners', async (pg) => { await pg.hover('#fscd-master-shell-top .fscd-has-mega:nth-of-type(5) .fscd-nav-trigger'); }],
    ['sticky header', async (pg) => { await pg.mouse.move(5, 600); await pg.evaluate(() => window.scrollTo(0, 900)); await pg.waitForTimeout(80); }],
  ],
  mobile: [
    ['menu toggle', async (pg) => { await pg.click('#fscd-menu-toggle'); }],
    ['menu Programs', async (pg) => { await pg.click('#fscd-master-shell-top .fscd-has-mega:nth-of-type(3) .fscd-nav-trigger'); }],
  ],
};

async function state(pg) {
  await pg.clock.runFor(400);
  await pg.waitForTimeout(80);
  const s = await pg.evaluate(() => ({
    navOpen: !!document.querySelector('#fscd-nav.fscd-open'),
    megaOpen: Array.from(document.querySelectorAll('.fscd-has-mega.fscd-mega-open .fscd-nav-trigger')).map((b) => b.textContent.trim()),
    compact: !!document.querySelector('#fscd-master-header.fscd-header-compact'),
    scrollY: Math.round(window.scrollY),
  }));
  const shot = PNG.sync.read(await pg.screenshot({ animations: 'disabled' }));
  return { s, shot };
}

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const shells = V.loadShells();
  const browser = await chromium.launch();
  const rows = [];
  let failed = 0;
  for (const p of V.manifest.pages.filter((x) => !only.length || only.includes(x.slug))) {
    for (const [vpName, vp] of [['desktop', { width: 1440, height: 900 }], ['mobile', { width: 390, height: 844 }]]) {
      for (const [label, act] of STEPS[vpName]) {
        const errRef = [], errCand = [];
        const ref = await open(browser, V.REF_ORIGIN + '/', V.referenceHtml(p, shells[p.slug]), vp, errRef);
        const cand = await open(browser, V.BASE + p.url, null, vp, errCand);
        let a, b, err = '';
        try { await act(ref.page); a = await state(ref.page); await act(cand.page); b = await state(cand.page); } catch (e) { err = String(e.message).split('\n')[0]; }
        await ref.ctx.close(); await cand.ctx.close();
        const cmp = a && b ? V.compare(a.shot, b.shot) : null;
        const same = a && b && JSON.stringify(a.s) === JSON.stringify(b.s);
        const ok = !err && same && cmp.n === 0 && errCand.length <= errRef.length;
        if (!ok) failed++;
        if (cmp && cmp.n) fs.writeFileSync(path.join(OUT, `behaviour-${p.slug}-${vpName}-${label.replace(/\W+/g, '-')}.png`), PNG.sync.write(V.sideBySide(a.shot, b.shot, cmp.diff)));
        rows.push({ page: p.slug, viewport: vpName, step: label, ok, state: b ? b.s : null, refState: a ? a.s : null, pixels: cmp ? cmp.n : null, error: err, jsErrors: errCand });
        console.log((ok ? 'PASS ' : 'FAIL ') + `${p.slug} ${vpName} ${label}`.padEnd(46) + (b ? JSON.stringify(b.s) : '') + (cmp ? ' ' + cmp.n + ' px' : '') + (same === false ? ' STATE DIFFERS from original ' + JSON.stringify(a.s) : '') + (err ? ' ' + err : '') + (errCand.length ? ' JS errors: ' + errCand.join('; ') : ''));
      }
    }
    if (p.slug === 'contact') {
      const errs = [];
      const { ctx, page } = await open(browser, V.BASE + p.url, null, { width: 1440, height: 900 }, errs);
      const btn = await page.$('[data-topic]:not(.active)');
      const topic = btn ? await btn.getAttribute('data-topic') : null;
      if (btn) await btn.click();
      const st = await page.evaluate(() => ({
        topic: document.getElementById('fscd-c-topic').value, badge: document.getElementById('fscd-c-topicbadge').textContent,
        subject: document.getElementById('fscd-c-subject').value, mail: document.getElementById('fscd-c-mail-subject').value,
        names: Array.from(document.querySelectorAll('#fscd-c-form [name]')).map((x) => x.name).join(','),
      }));
      await ctx.close();
      const ok = topic && st.topic === topic && st.badge === topic && st.subject === topic && st.mail === 'FSCD Website: ' + topic && !errs.length;
      if (!ok) failed++;
      rows.push({ page: 'contact', viewport: 'desktop', step: 'topic router', ok, state: st, jsErrors: errs });
      console.log((ok ? 'PASS ' : 'FAIL ') + 'contact topic router → ' + topic + ' ' + JSON.stringify(st));
    }
  }
  await browser.close();
  fs.writeFileSync(path.join(OUT, 'behaviour.json'), JSON.stringify(rows, null, 2));
  console.log(`\n${rows.length - failed}/${rows.length} passed`);
  process.exit(failed ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(1); });
