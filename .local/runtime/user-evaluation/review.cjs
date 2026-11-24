const { chromium } = require('../../browser/node_modules/playwright-core');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const fixture = require('./fixture.json');
const base = 'http://localhost/bacamudah/';
const report = { checks: [], pages: [], accessibility: [], browserErrors: [], observations: {} };
let browser;
function save() { fs.writeFileSync(path.join(__dirname, 'report.json'), JSON.stringify(report, null, 2)); }
async function check(name, run) {
  try { const detail = await run(); report.checks.push({ name, passed: true, detail }); console.log('LULUS ' + name); }
  catch (error) { report.checks.push({ name, passed: false, error: error.message }); console.log('GAGAL ' + name + ': ' + error.message); }
  save();
}
async function capture(p, name, audit = true) {
  await p.evaluate(() => document.fonts.ready);
  await p.screenshot({ path: path.join(__dirname, name + '.png'), fullPage: true });
  const detail = await p.evaluate(() => {
    const box = selector => { const el = document.querySelector(selector); if (!el) return null; const r = el.getBoundingClientRect(); return { top: Math.round(r.top), width: Math.round(r.width), height: Math.round(r.height) }; };
    return {
      viewport: { width: innerWidth, height: innerHeight }, scrollWidth: document.documentElement.scrollWidth,
      headings: [...document.querySelectorAll('h1,h2,h3')].map(el => ({ text: el.innerText, font: getComputedStyle(el).fontFamily, size: getComputedStyle(el).fontSize })),
      firstCard: box('.kartu-bacaan'), privateList: box('.private-list'), editor: box('.editor-card'),
      bodyFont: getComputedStyle(document.body).fontFamily,
      inputs: [...document.querySelectorAll('input:not([type=hidden]),textarea,select')].map(el => ({ id: el.id, fontSize: getComputedStyle(el).fontSize })),
    };
  });
  report.pages.push({ name, ...detail });
  if (audit) {
    await p.addScriptTag({ path: require.resolve('../../browser/node_modules/axe-core/axe.min.js') });
    const result = await p.evaluate(async () => {
      const result = await axe.run({ runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'] } });
      return result.violations.map(v => ({ id: v.id, impact: v.impact, help: v.help, nodes: v.nodes.map(n => ({ target: n.target, reason: n.failureSummary })) }));
    });
    report.accessibility.push({ name, violations: result });
    console.log('AKSESIBILITAS ' + name + ': ' + result.length);
  }
  save();
}
(async () => {
  browser = await chromium.launch({ headless: true, executablePath: 'C:/Users/MSI/AppData/Local/ms-playwright/chromium-1234/chrome-win64/chrome.exe' });
  const p = await browser.newPage({ viewport: { width: 1366, height: 768 }, reducedMotion: 'reduce' });
  p.setDefaultTimeout(10000);
  p.on('pageerror', error => report.browserErrors.push(error.message));
  p.on('dialog', dialog => dialog.accept());
  await p.goto(base + 'login.php');
  await p.locator('#email').fill(fixture.email);
  await p.locator('#password').fill('EvalBaca2026!');
  await Promise.all([p.waitForURL('**/index.php'), p.locator('.auth-form [type=submit]').click()]);
  await capture(p, '01-library-desktop');
  const firstTitle = await p.locator('.kartu-bacaan h3').first().innerText();
  const firstRead = await p.locator('.sampul-link').first().getAttribute('href');
  report.observations.library = { firstTitle, firstRead, cards: await p.locator('.kartu-bacaan').count(), count: await p.locator('.search-result-note').innerText() };
  await check('Pencarian judul dan hasil kosong', async () => {
    await p.locator('#q').fill(firstTitle);
    await Promise.all([p.waitForNavigation(), p.locator('.library-search [type=submit]').click()]);
    assert.ok(await p.locator('.kartu-bacaan').count() >= 1);
    await p.goto(base + 'index.php?q=evaluasi-tidak-ada-' + Date.now());
    assert.ok(await p.locator('.empty-state').isVisible());
    await capture(p, '02-library-empty', false);
  });
  await check('Filter kategori', async () => {
    await p.goto(base + 'index.php');
    const option = await p.locator('#kategori option').nth(1).getAttribute('value');
    await p.locator('#kategori').selectOption(option);
    await Promise.all([p.waitForNavigation(), p.locator('.library-search [type=submit]').click()]);
    assert.equal(new URL(p.url()).searchParams.get('kategori'), option);
    return { result: await p.locator('.search-result-note').innerText() };
  });
  await p.goto(base + 'teks-saya.php'); await capture(p, '03-private-empty-desktop');
  await p.goto(base + 'bookmarks.php'); await capture(p, '04-bookmark-empty-desktop');
  await check('Tambah dan tampilkan bookmark', async () => {
    await p.goto(base + firstRead);
    await Promise.all([p.waitForNavigation(), p.getByRole('button', { name: 'Tambah bookmark', exact: true }).click()]);
    await p.goto(base + 'bookmarks.php');
    assert.equal(await p.locator('.kartu-bacaan').count(), 1);
    await capture(p, '05-bookmark-filled-desktop');
  });
  await p.goto(base + firstRead); await capture(p, '06-reader-desktop');
  await check('Mode fokus dan Escape', async () => {
    await p.locator('#btn-fokus').click();
    assert.equal(await p.locator('#btn-fokus').getAttribute('aria-pressed'), 'true');
    await capture(p, '07-reader-focus', false);
    await p.keyboard.press('Escape');
    assert.equal(await p.locator('#btn-fokus').getAttribute('aria-pressed'), 'false');
  });
  await check('Pratinjau ukuran huruf dan tiga tema', async () => {
    await p.locator('[data-reading-preset=besar]').click();
    assert.equal(await p.locator('#ukuran').inputValue(), '28');
    for (const theme of ['krem', 'kontras', 'normal']) {
      await p.locator('[name=tema][value=' + theme + ']').check();
      assert.equal(await p.locator('body').getAttribute('data-tema'), theme);
      if (theme !== 'normal') await capture(p, '08-reader-theme-' + theme);
    }
    await p.locator('#btn-reset').click();
  });
  report.observations.speech = await p.evaluate(() => ({ supported: 'speechSynthesis' in window, voices: 'speechSynthesis' in window ? speechSynthesis.getVoices().map(v => v.lang) : [] }));
  await check('Buat teks pribadi sementara', async () => {
    await p.goto(base + 'teks-saya.php');
    await p.locator('#judul').fill('Catatan evaluasi pengalaman membaca');
    await p.locator('#isi_teks').fill(Array.from({ length: 8 }, (_, i) => 'Paragraf ' + (i + 1) + '. ' + 'Setiap pembaca dapat menyesuaikan tampilan teks dengan kebutuhannya. '.repeat(5)).join('\n\n'));
    await Promise.all([p.waitForURL('**/read.php?id=*'), p.getByRole('button', { name: 'Simpan & mulai membaca' }).click()]);
    report.observations.privateRead = p.url();
    assert.match(await p.locator('#judul-teks').innerText(), /Catatan evaluasi/);
    await p.goto(base + 'teks-saya.php'); await capture(p, '09-private-filled-desktop');
  });
  await check('Lanjutkan bacaan terakhir', async () => {
    await p.goto(base + 'index.php');
    assert.ok(await p.locator('.continue-reading').isVisible());
  });
  await p.setViewportSize({ width: 390, height: 844 });
  for (const [url, name] of [['index.php', '10-library-mobile'], ['teks-saya.php', '11-private-mobile'], ['bookmarks.php', '12-bookmark-mobile'], [firstRead, '13-reader-mobile']]) {
    await p.goto(base + url); await capture(p, name);
  }
  await check('Pengaturan ponsel, pembatasan fokus, dan Escape', async () => {
    await p.locator('[data-settings-toggle]').click();
    assert.equal(await p.locator('[data-settings-toggle]').getAttribute('aria-expanded'), 'true');
    await capture(p, '14-settings-mobile');
    await p.keyboard.press('Shift+Tab');
    assert.equal(await p.evaluate(() => document.activeElement.id), 'btn-reset');
    await p.keyboard.press('Escape');
    assert.equal(await p.locator('[data-settings-toggle]').getAttribute('aria-expanded'), 'false');
  });
  await check('Menu ponsel dan pemulihan fokus', async () => {
    await p.goto(base + 'index.php');
    await p.locator('[data-nav-toggle]').click();
    await capture(p, '15-navigation-mobile');
    await p.keyboard.press('Escape');
    assert.equal(await p.evaluate(() => document.activeElement.hasAttribute('data-nav-toggle')), true);
  });
  await check('Halaman utama pada lebar 320 px', async () => {
    await p.setViewportSize({ width: 320, height: 740 });
    for (const url of ['index.php', 'teks-saya.php', 'bookmarks.php', firstRead]) {
      await p.goto(base + url);
      assert.ok(await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth), url);
    }
  });
  await check('Hapus bookmark', async () => {
    await p.goto(base + 'bookmarks.php');
    await Promise.all([p.waitForNavigation(), p.locator('.kartu-bacaan [type=submit]').click()]);
    assert.equal(await p.locator('.kartu-bacaan').count(), 0);
  });
  await check('Tidak ada galat JavaScript', async () => assert.deepEqual(report.browserErrors, []));
})().catch(error => { report.fatal = error.stack; console.error(error); process.exitCode = 1; }).finally(async () => { save(); if (browser) await browser.close(); });
