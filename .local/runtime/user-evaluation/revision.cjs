const { chromium } = require('../../browser/node_modules/playwright-core');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const fixture = require('./fixture.json');
const base = process.env.BACA_TEST_BASE || 'http://localhost/bacamudah/';
const report = { checks: [], accessibility: [], measurements: [], errors: [] };
let browser;
const output = name => path.join(__dirname, name);
function save() { fs.writeFileSync(output('revision-report.json'), JSON.stringify(report, null, 2)); }
async function check(name, run) {
  try { await run(); report.checks.push({ name, passed: true }); console.log('LULUS ' + name); }
  catch (error) { report.checks.push({ name, passed: false, error: error.stack }); console.log('GAGAL ' + name + ': ' + error.message); }
  save();
}
async function login(p) {
  await p.goto(base + 'login.php');
  await p.locator('#email').fill(fixture.email); await p.locator('#password').fill('EvalBaca2026!');
  await Promise.all([p.waitForURL('**/index.php'), p.locator('.auth-form [type=submit]').click()]);
}
async function submit(p, locator) { await Promise.all([p.waitForNavigation(), locator.click()]); }
async function shot(p, name) { await p.evaluate(() => document.fonts.ready); await p.screenshot({ path: output('revised-' + name + '.png'), fullPage: true }); }
async function axe(p, name) {
  await p.addScriptTag({ path: require.resolve('../../browser/node_modules/axe-core/axe.min.js') });
  const violations = await p.evaluate(async () => (await axe.run({ runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'] } })).violations.map(v => ({ id: v.id, nodes: v.nodes.map(n => n.target) })));
  report.accessibility.push({ name, violations }); save(); assert.deepEqual(violations, [], name);
}
(async () => {
  browser = await chromium.launch({ headless: true, executablePath: 'C:/Users/MSI/AppData/Local/ms-playwright/chromium-1234/chrome-win64/chrome.exe' });
  const p = await browser.newPage({ viewport: { width: 1366, height: 768 }, reducedMotion: 'reduce' });
  p.setDefaultTimeout(10000); p.on('dialog', d => d.accept()); p.on('pageerror', e => report.errors.push(e.message));
  await login(p);
  let readHref, privateId;
  await check('Perpustakaan: pencarian, kategori, dan hasil kosong', async () => {
    const first = p.locator('.kartu-bacaan').first(); const title = await first.locator('h3').innerText(); readHref = await first.locator('.sampul-link').getAttribute('href');
    await p.locator('#q').fill(title); await submit(p, p.locator('.library-search [type=submit]')); assert.ok(await p.locator('.kartu-bacaan').count() > 0);
    await p.goto(base + 'index.php'); const category = await p.locator('#kategori option').nth(1).getAttribute('value'); await p.locator('#kategori').selectOption(category); await submit(p, p.locator('.library-search [type=submit]')); assert.equal(new URL(p.url()).searchParams.get('kategori'), category);
    await p.goto(base + 'index.php?q=tidak-ada-evaluasi-' + Date.now()); assert.ok(await p.locator('.empty-state').isVisible()); await axe(p, 'library-empty');
    await p.goto(base + 'index.php'); await shot(p, 'library-desktop'); await axe(p, 'library-desktop');
  });
  await check('Teks Saya: daftar dahulu, formulir tertutup dan dapat dibuka', async () => {
    await p.goto(base + 'teks-saya.php'); assert.ok(await p.getByRole('heading', { name: 'Bacaan tersimpan' }).isVisible()); assert.equal(await p.locator('#tulis').getAttribute('open'), null); assert.equal(await p.locator('#judul').isVisible(), false); await axe(p, 'private-empty');
    await p.getByRole('link', { name: 'Tambah teks', exact: true }).click(); assert.ok(await p.locator('#judul').isVisible());
  });
  await check('Validasi gagal mempertahankan isian dan formulir terbuka', async () => {
    await p.locator('#isi_teks').fill('Teks ini harus dipertahankan saat judul belum diisi.'); await submit(p, p.getByRole('button', { name: 'Simpan & mulai membaca' }));
    assert.ok(await p.getByRole('alert').isVisible()); assert.ok(await p.locator('#judul').isVisible()); assert.equal(await p.locator('#isi_teks').inputValue(), 'Teks ini harus dipertahankan saat judul belum diisi.');
    await p.locator('#judul').fill('Catatan revisi halaman pengguna'); await p.locator('#isi_teks').fill(Array.from({ length: 8 }, (_, i) => `Paragraf ${i + 1}. ` + 'Pembaca dapat menyesuaikan tampilan teks sesuai kebutuhan mereka. '.repeat(6)).join('\n\n'));
    await Promise.all([p.waitForURL('**/read.php?id=*'), p.getByRole('button', { name: 'Simpan & mulai membaca' }).click()]); privateId = new URL(p.url()).searchParams.get('id');
    assert.equal(await p.locator('#isi-bacaan p').count(), 8);
  });
  await check('Ubah teks, bookmark tambah/hapus, dan konfirmasi hapus batal', async () => {
    await p.goto(base + 'teks-edit.php?id=' + privateId); await p.locator('#judul').fill('Catatan revisi diperbarui'); await submit(p, p.getByRole('button', { name: 'Simpan perubahan', exact: true })); assert.equal(await p.locator('#judul-teks').innerText(), 'Catatan revisi diperbarui');
    await submit(p, p.getByRole('button', { name: 'Tambah bookmark', exact: true })); await p.goto(base + 'bookmarks.php'); assert.equal(await p.locator('.kartu-bacaan').count(), 1); await axe(p, 'bookmark-filled'); await submit(p, p.locator('.kartu-bacaan [type=submit]')); assert.equal(await p.locator('.kartu-bacaan').count(), 0);
    await p.goto(base + 'teks-saya.php'); await p.getByRole('button', { name: 'Hapus Catatan revisi diperbarui', exact: true }).click(); await p.getByRole('button', { name: 'Tetap simpan', exact: true }).click(); assert.ok(await p.getByRole('heading', { name: 'Catatan revisi diperbarui' }).isVisible()); await shot(p, 'private-desktop');
  });
  await check('Font antarmuka konsisten, font isi tetap dapat dipilih', async () => {
    await p.goto(base + 'read.php?id=' + privateId); assert.match(await p.locator('h1').evaluate(e => getComputedStyle(e).fontFamily), /Manrope/); await p.locator('#font').selectOption('verdana'); assert.match(await p.locator('#isi-bacaan').evaluate(e => getComputedStyle(e).fontFamily), /Verdana/); await p.locator('#btn-reset').click();
    await p.locator('#btn-fokus').click(); await p.locator('#focus-next').click(); assert.equal(await p.locator('#isi-bacaan p.aktif').getAttribute('data-paragraph'), '1'); await p.keyboard.press('Escape'); assert.equal(await p.locator('#btn-fokus').getAttribute('aria-pressed'), 'false'); await axe(p, 'reader-desktop'); await shot(p, 'reader-desktop');
  });
  await check('Desktop: simpan terlihat sebelum dan sesudah halaman digulir', async () => {
    await p.goto(base + 'read.php?id=' + privateId);
    for (const scroll of [0, 500]) {
      await p.evaluate(y => window.scrollTo(0, y), scroll);
      await p.waitForTimeout(100);
      const bottom = await p.locator('#form-atur [type=submit]').evaluate(e => e.getBoundingClientRect().bottom);
      assert.ok(bottom <= 768, 'Tombol simpan di luar layar desktop: ' + bottom);
    }
  });
  await check('Ponsel: bacaan awal terlihat, daftar Teks Saya di atas, tanpa overflow', async () => {
    for (const width of [390, 320, 768, 1024]) {
      await p.setViewportSize({ width, height: 844 });
      for (const url of ['index.php', 'teks-saya.php', 'bookmarks.php', 'read.php?id=' + privateId]) {
        await p.goto(base + url); await p.evaluate(() => document.fonts.ready); assert.ok(await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth), width + ' ' + url);
        if (width <= 390 && url === 'index.php') {
          const top = await p.locator('.kartu-bacaan h3').first().evaluate(e => e.getBoundingClientRect().top); report.measurements.push({ width, firstTitleTop: top }); assert.ok(top < 744, 'Judul pertama harus terlihat di layar awal');
        }
        if (width <= 390 && url === 'teks-saya.php') { const top = await p.locator('.private-list').evaluate(e => e.getBoundingClientRect().top); report.measurements.push({ width, privateListTop: top }); assert.ok(top < 400); assert.equal(await p.locator('#judul').isVisible(), false); }
        if (width === 390) { await axe(p, 'mobile-' + url.split('?')[0]); await shot(p, url.split('.')[0] + '-mobile'); }
      }
    }
  });
  await check('Panel baca: simpan selalu terlihat, area pengaturan dapat digulir, fokus dan Escape', async () => {
    for (const [width, height] of [[390, 844], [320, 568], [844, 390]]) {
      await p.setViewportSize({ width, height }); await p.goto(base + 'read.php?id=' + privateId);
      const position = await p.locator('[data-settings-toggle]').evaluate(e => getComputedStyle(e).position); assert.equal(position, 'static');
      await p.locator('[data-settings-toggle]').click();
      const bounds = await p.locator('#form-atur [type=submit]').evaluate(e => ({ top: e.getBoundingClientRect().top, bottom: e.getBoundingClientRect().bottom, viewport: innerHeight }));
      report.measurements.push({ width, height, saveButton: bounds }); assert.ok(bounds.top >= 0 && bounds.bottom <= height, JSON.stringify(bounds));
      await p.locator('.settings-fields').evaluate(e => { e.scrollTop = e.scrollHeight; }); assert.ok(await p.locator('#btn-reset').isVisible());
      await p.locator('[data-settings-close]').first().focus(); await p.keyboard.press('Shift+Tab'); assert.equal(await p.evaluate(() => document.activeElement.id), 'btn-reset');
      if (width === 390) { await axe(p, 'settings-mobile'); await shot(p, 'settings-mobile'); }
      await p.keyboard.press('Escape'); assert.equal(await p.locator('[data-settings-toggle]').getAttribute('aria-expanded'), 'false'); assert.equal(await p.evaluate(() => document.activeElement.hasAttribute('data-settings-toggle')), true);
    }
  });
  await check('Pengaturan tersimpan, tiga tema, dan perubahan orientasi', async () => {
    await p.setViewportSize({ width: 390, height: 844 }); await p.goto(base + 'read.php?id=' + privateId); await p.locator('[data-settings-toggle]').click(); await p.locator('[data-reading-preset=besar]').click(); await p.locator('#form-atur [type=submit]').click(); await p.waitForFunction(() => document.querySelector('#settings-status').textContent === 'Pengaturan berhasil disimpan.');
    await p.keyboard.press('Escape'); await p.reload(); await p.locator('[data-settings-toggle]').click(); assert.equal(await p.locator('#ukuran').inputValue(), '28');
    for (const theme of ['krem', 'kontras', 'normal']) { await p.locator('[name=tema][value=' + theme + ']').check(); await axe(p, 'settings-theme-' + theme); }
    await p.setViewportSize({ width: 1366, height: 768 }); assert.equal(await p.locator('[data-settings-toggle]').getAttribute('aria-expanded'), 'false'); assert.equal(await p.locator('body').evaluate(e => e.style.overflow), '');
  });
  await check('Tanpa JavaScript: buka formulir, buat teks, dan simpan pengaturan', async () => {
    const ctx = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 844 } }); const q = await ctx.newPage(); await login(q); await q.goto(base + 'teks-saya.php'); await q.getByRole('link', { name: 'Tambah teks', exact: true }).click(); assert.ok(await q.locator('#judul').isVisible()); await q.locator('#judul').fill('Catatan tanpa JavaScript'); await q.locator('#isi_teks').fill('Bacaan baru tanpa JavaScript tetap dapat disimpan.'); await submit(q, q.getByRole('button', { name: 'Simpan & mulai membaca' })); assert.match(await q.locator('#judul-teks').innerText(), /tanpa JavaScript/); await q.locator('[name=tema][value=krem]').check(); await submit(q, q.locator('#form-atur [type=submit]')); assert.equal(await q.locator('body').getAttribute('data-tema'), 'krem'); await ctx.close();
  });
  await check('Hapus teks dan menu ponsel tetap berfungsi', async () => {
    await p.goto(base + 'teks-saya.php'); await p.getByRole('button', { name: 'Hapus Catatan revisi diperbarui', exact: true }).click(); await submit(p, p.getByRole('button', { name: 'Ya, hapus', exact: true })); assert.equal(await p.getByRole('heading', { name: 'Catatan revisi diperbarui' }).count(), 0);
    await p.setViewportSize({ width: 390, height: 844 }); await p.locator('[data-nav-toggle]').click(); await p.keyboard.press('Escape'); assert.equal(await p.locator('[data-nav-toggle]').getAttribute('aria-expanded'), 'false'); assert.deepEqual(report.errors, []);
  });
})().catch(error => { report.fatal = error.stack; console.error(error); process.exitCode = 1; }).finally(async () => { save(); if (browser) await browser.close(); if (report.checks.some(c => !c.passed)) process.exitCode = 1; });
