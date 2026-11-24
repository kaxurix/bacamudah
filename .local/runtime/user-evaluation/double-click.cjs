const { chromium } = require('../../browser/node_modules/playwright-core');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const fixture = require('./fixture.json');
const base = process.env.BACA_TEST_BASE || 'http://localhost/bacamudah/';
const report = { checks: [], errors: [] };
let browser;
function save() { fs.writeFileSync(path.join(__dirname, 'double-click-report.json'), JSON.stringify(report, null, 2)); }
async function login(p) {
  await p.goto(base + 'login.php'); await p.locator('#email').fill(fixture.email); await p.locator('#password').fill('EvalBaca2026!');
  await Promise.all([p.waitForURL('**/index.php'), p.locator('.auth-form [type=submit]').click()]);
}
async function mockSpeech(p) {
  await p.evaluate(() => {
    window.__speech = { utterances: [], cancels: 0 };
    speechSynthesis.getVoices = () => [];
    speechSynthesis.cancel = () => { __speech.cancels++; };
    speechSynthesis.speak = utterance => { __speech.utterances.push(utterance); if (utterance.onstart) utterance.onstart(); };
  });
}
async function check(name, run) {
  await run(); report.checks.push({ name, passed: true }); console.log('LULUS ' + name); save();
}
(async () => {
  browser = await chromium.launch({ headless: true, executablePath: 'C:/Users/MSI/AppData/Local/ms-playwright/chromium-1234/chrome-win64/chrome.exe' });
  const p = await browser.newPage({ viewport: { width: 1366, height: 768 }, reducedMotion: 'reduce' });
  p.on('pageerror', e => report.errors.push(e.message)); p.on('dialog', d => d.accept());
  await login(p); await p.goto(base + 'teks-saya.php?tulis=1#tulis');
  await p.locator('#judul').fill('Evaluasi klik ganda dan baca nyaring');
  await p.locator('#isi_teks').fill(Array.from({ length: 5 }, (_, i) => 'Paragraf ' + (i + 1) + '. ' + 'Bacaan ini digunakan untuk menguji perpindahan paragraf dan penghentian suara. '.repeat(6)).join('\n\n'));
  await Promise.all([p.waitForURL('**/read.php?id=*'), p.getByRole('button', { name: 'Simpan & mulai membaca' }).click()]);
  const readUrl = p.url(); report.readUrl = readUrl; await mockSpeech(p);
  await check('Klik sekali fokus, klik dua kali membacakan dari paragraf yang dipilih', async () => {
    const paragraph = p.locator('#isi-bacaan p').nth(1); const expected = await paragraph.textContent();
    await paragraph.click(); assert.equal(await p.evaluate(() => __speech.utterances.length), 0); assert.equal(await p.locator('#isi-bacaan p.aktif').getAttribute('data-paragraph'), '1');
    await paragraph.dblclick(); assert.equal(await p.evaluate(() => __speech.utterances.length), 1, 'Klik ganda harus memulai tepat satu bacaan'); assert.equal(await p.evaluate(() => __speech.utterances.at(-1).text), expected); assert.equal(await p.locator('#btn-baca').getAttribute('aria-pressed'), 'true');
    assert.equal(await p.evaluate(() => window.getSelection().isCollapsed), true, 'Klik ganda tidak meninggalkan seleksi kata di atas sorotan fokus');
  });
  await check('Berhenti tetap terjangkau saat menggulir dan navigasi fokus tidak tertutup', async () => {
    await p.evaluate(() => window.scrollBy(0, 500));
    const bounds = await p.locator('#btn-stop-reading').boundingBox(); assert.ok(bounds.y >= 0 && bounds.y + bounds.height <= 768);
    const toolbar = await p.locator('.reader-topline').boundingBox();
    const nav = await p.locator('.focus-navigation').boundingBox(); assert.ok(nav.y >= toolbar.y + toolbar.height);
    assert.ok(await p.locator('#btn-stop-reading').isVisible());
  });
  await check('Klik ganda paragraf lain mengganti bacaan, callback lama diabaikan', async () => {
    const oldCount = await p.evaluate(() => __speech.utterances.length); const selected = p.locator('#isi-bacaan p').nth(3); const text = await selected.textContent(); await selected.dblclick();
    assert.equal(await p.evaluate(() => __speech.utterances.length), oldCount + 1); assert.equal(await p.evaluate(() => __speech.utterances.at(-1).text), text);
    await p.evaluate(() => __speech.utterances[0].onend()); assert.equal(await p.evaluate(() => __speech.utterances.length), oldCount + 1);
  });
  await check('Tombol Berhenti menghentikan suara dan mempertahankan fokus paragraf', async () => {
    const count = await p.evaluate(() => __speech.utterances.length); await p.locator('#btn-stop-reading').click();
    assert.equal(await p.locator('#btn-baca').getAttribute('aria-pressed'), 'false'); assert.equal(await p.locator('#btn-stop-reading').isVisible(), false);
    assert.equal(await p.locator('#isi-bacaan p.aktif').getAttribute('data-paragraph'), '3'); assert.equal(await p.evaluate(() => document.activeElement.dataset.paragraph), '3');
    await p.evaluate(() => __speech.utterances.at(-1).onend()); assert.equal(await p.evaluate(() => __speech.utterances.length), count);
  });
  await check('Keyboard Ctrl+Enter mulai membaca, Escape menghentikan dan keluar fokus', async () => {
    const paragraph = p.locator('#isi-bacaan p').nth(2); const text = await paragraph.textContent(); await paragraph.focus(); await paragraph.press('Control+Enter');
    assert.equal(await p.evaluate(() => __speech.utterances.at(-1).text), text); assert.equal(await p.locator('#btn-baca').getAttribute('aria-pressed'), 'true');
    await p.keyboard.press('Escape'); assert.equal(await p.locator('#btn-baca').getAttribute('aria-pressed'), 'false'); assert.equal(await p.locator('#btn-stop-reading').isVisible(), false); assert.equal(await p.locator('body').evaluate(e => e.classList.contains('mode-fokus')), false);
  });
  await check('Tombol Bacakan teks tetap bisa mulai dan menghentikan bacaan', async () => {
    await p.locator('#btn-baca').click(); assert.equal(await p.evaluate(() => __speech.utterances.at(-1).text), await p.locator('#isi-bacaan p').first().textContent());
    await p.locator('#btn-baca').click(); assert.equal(await p.locator('#btn-baca').getAttribute('aria-pressed'), 'false'); assert.equal(await p.locator('#btn-stop-reading').isVisible(), false);
  });
  await check('Bacaan berlanjut ke paragraf berikut lalu selesai dan tombol Berhenti disembunyikan', async () => {
    await p.locator('#isi-bacaan p').nth(3).dblclick(); await p.evaluate(() => __speech.utterances.at(-1).onend());
    assert.equal(await p.evaluate(() => __speech.utterances.at(-1).text), await p.locator('#isi-bacaan p').nth(4).textContent());
    await p.evaluate(() => __speech.utterances.at(-1).onend()); assert.equal(await p.locator('#btn-stop-reading').isVisible(), false); assert.equal(await p.locator('#btn-baca').getAttribute('aria-pressed'), 'false'); assert.match(await p.locator('#info-suara').innerText(), /Bacaan selesai/);
  });
  await check('Galat audio dan kegagalan langsung tidak meninggalkan status sedang membaca', async () => {
    await p.locator('#isi-bacaan p').first().dblclick(); await p.evaluate(() => __speech.utterances.at(-1).onerror({ error: 'synthesis-failed' })); assert.equal(await p.locator('#btn-stop-reading').isVisible(), false); assert.match(await p.locator('#info-suara').innerText(), /belum bisa diputar/);
    await p.evaluate(() => { speechSynthesis.speak = () => { throw new Error('Provider tidak tersedia'); }; }); await p.locator('#isi-bacaan p').first().dblclick(); assert.equal(await p.locator('#btn-baca').getAttribute('aria-pressed'), 'false'); assert.equal(await p.locator('#btn-stop-reading').isVisible(), false); assert.match(await p.locator('#info-suara').innerText(), /belum bisa diputar/); await mockSpeech(p);
  });
  await check('Suara Indonesia tidak tersedia: pengguna mendapat penjelasan fallback', async () => {
    await p.evaluate(() => { speechSynthesis.getVoices = () => [{ lang: 'en-US' }]; }); await p.locator('#isi-bacaan p').first().dblclick(); assert.match(await p.locator('#info-suara').innerText(), /Suara bahasa Indonesia belum tersedia/); await p.locator('#btn-stop-reading').click();
  });
  await check('Desktop dan ponsel: tombol Berhenti terlihat, modal pengaturan tidak menghentikan suara', async () => {
    for (const [width, height] of [[1366, 768], [390, 844], [320, 568], [844, 390]]) {
      await p.setViewportSize({ width, height }); await p.goto(readUrl); await mockSpeech(p); await p.locator('#isi-bacaan p').nth(2).dblclick();
      assert.ok(await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth), width + ' overflow');
      const rect = await p.locator('#btn-stop-reading').boundingBox(); assert.ok(rect.y >= 0 && rect.y + rect.height <= height, JSON.stringify(rect));
      if (width <= 1060) { await p.locator('[data-settings-toggle]').click(); await p.keyboard.press('Escape'); assert.equal(await p.locator('#btn-baca').getAttribute('aria-pressed'), 'true'); }
      if (width === 1366 || width === 390) {
        await p.screenshot({ path: path.join(__dirname, 'double-click-' + width + '.png') });
        await p.addScriptTag({ path: require.resolve('../../browser/node_modules/axe-core/axe.min.js') });
        const violations = await p.evaluate(async () => (await axe.run({ runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'] } })).violations.map(v => ({ id: v.id, nodes: v.nodes.map(n => n.target) })));
        assert.deepEqual(violations, [], 'Aksesibilitas saat suara berjalan');
      }
      await p.locator('#btn-stop-reading').click(); assert.equal(await p.locator('#btn-baca').getAttribute('aria-pressed'), 'false');
    }
  });
  await check('Browser tanpa suara tetap bisa fokus tanpa galat JavaScript', async () => {
    const context = await browser.newContext(); await context.addInitScript(() => { delete window.speechSynthesis; delete window.SpeechSynthesisUtterance; }); const q = await context.newPage(); const errors = []; q.on('pageerror', e => errors.push(e.message)); await login(q); await q.goto(readUrl);
    assert.ok(await q.locator('#btn-baca').isDisabled()); await q.locator('#isi-bacaan p').nth(1).dblclick(); assert.equal(await q.locator('#isi-bacaan p.aktif').getAttribute('data-paragraph'), '1'); assert.equal(await q.locator('#btn-stop-reading').isVisible(), false); assert.match(await q.locator('#info-suara').innerText(), /belum menyediakan baca nyaring/); assert.deepEqual(errors, []); await context.close();
  });
  await check('Tidak ada galat JavaScript selama interaksi', async () => assert.deepEqual(report.errors, []));
})().catch(e => { report.failure = e.stack; console.error(e); process.exitCode = 1; }).finally(async () => { save(); if (browser) await browser.close(); });
