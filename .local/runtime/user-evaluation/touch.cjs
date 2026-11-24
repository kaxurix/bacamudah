const { chromium } = require('../../browser/node_modules/playwright-core');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const fixture = require('./fixture.json');
const base = process.env.BACA_TEST_BASE || 'http://127.0.0.1:8000/';
const report = { checks: [], errors: [], eventTrace: [] };
let browser;
const save = () => fs.writeFileSync(path.join(__dirname, 'touch-report.json'), JSON.stringify(report, null, 2));
async function login(p) {
  await p.goto(base + 'login.php'); await p.locator('#email').fill(fixture.email); await p.locator('#password').fill('EvalBaca2026!');
  await Promise.all([p.waitForURL('**/index.php'), p.locator('.auth-form [type=submit]').click()]);
}
async function mockSpeech(p) {
  await p.evaluate(() => {
    window.__speech = { utterances: [], canceled: 0, activeAtStart: [] };
    window.__touchTrace = [];
    window.__allTouchEvents = [];
    speechSynthesis.getVoices = () => [];
    speechSynthesis.speak = u => { __speech.utterances.push(u); __speech.activeAtStart.push(navigator.userActivation.isActive); };
    speechSynthesis.cancel = () => { __speech.canceled++; };
    for (const type of ['touchstart', 'touchend', 'click', 'dblclick']) document.querySelector('#isi-bacaan').addEventListener(type, e => __touchTrace.push({ type, detail: e.detail, paragraph: e.target.closest('[data-paragraph]')?.dataset.paragraph, trusted: e.isTrusted }), true);
    for (const type of ['touchstart', 'touchend', 'click', 'dblclick']) document.addEventListener(type, e => __allTouchEvents.push({ type, target: e.target.id || e.target.closest('button')?.id || e.target.tagName, paragraph: e.target.closest('[data-paragraph]')?.dataset.paragraph, pointerType: e.pointerType, touchSource: e.sourceCapabilities?.firesTouchEvents, prevented: e.defaultPrevented, cancelable: e.cancelable, reading: document.querySelector('#btn-baca').getAttribute('aria-pressed') }));
  });
}
async function point(p, index = 0) {
  const el = p.locator('#isi-bacaan p').nth(index); await el.scrollIntoViewIfNeeded();
  const r = await el.boundingBox(); return { x: r.x + r.width / 2, y: r.y + Math.min(26, r.height / 2) };
}
async function check(name, run) {
  await run(); report.checks.push({ name, passed: true }); console.log('LULUS ' + name); save();
}
async function gesture(cdp, type, points) { await cdp.send('Input.dispatchTouchEvent', { type, touchPoints: points }); }
(async () => {
  browser = await chromium.launch({ headless: true, executablePath: 'C:/Users/MSI/AppData/Local/ms-playwright/chromium-1234/chrome-win64/chrome.exe' });
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, reducedMotion: 'reduce' });
  const p = await context.newPage(); p.on('pageerror', e => report.errors.push(e.message));
  await login(p); await p.goto(base + 'teks-saya.php?tulis=1#tulis'); await p.locator('#judul').fill('Uji ketukan layar sentuh');
  await p.locator('#isi_teks').fill('Paragraf pertama untuk uji sentuhan.\n\nParagraf kedua untuk uji ketukan berikutnya.\n\n' + 'Paragraf panjang untuk menguji gerakan gulir. '.repeat(45));
  await Promise.all([p.waitForURL('**/read.php?id=*'), p.getByRole('button', { name: 'Simpan & mulai membaca' }).click()]);
  report.readUrl = p.url(); await mockSpeech(p);
  const readUrl = p.url();
  const fresh = async () => { await p.reload(); await mockSpeech(p); };
  const spoken = () => p.evaluate(() => __speech.utterances.length);
  await check('Ketuk sekali fokus tanpa suara dan posisi paragraf tetap stabil', async () => {
    const target = await point(p); const before = await p.locator('#isi-bacaan p').first().boundingBox(); await p.touchscreen.tap(target.x, target.y);
    assert.equal(await spoken(), 0); assert.equal(await p.locator('#isi-bacaan p.aktif').getAttribute('data-paragraph'), '0');
    const after = await p.locator('#isi-bacaan p').first().boundingBox(); assert.ok(Math.abs(after.y - before.y) < 2, JSON.stringify({ before: before.y, after: after.y }));
    await p.locator('#isi-bacaan p').nth(1).evaluate(el => el.click()); assert.equal(await p.locator('#isi-bacaan p.aktif').getAttribute('data-paragraph'), '1', 'Aktivasi tanpa pointer dari teknologi bantu tetap diproses'); assert.equal(await spoken(), 0);
  });
  await check('Dua ketukan pada posisi sama membacakan satu kali dalam aktivasi pengguna', async () => {
    await fresh(); const target = await point(p); const scale = await p.evaluate(() => visualViewport.scale); await p.touchscreen.tap(target.x, target.y); await p.waitForTimeout(100); await p.touchscreen.tap(target.x, target.y);
    report.eventTrace = await p.evaluate(() => __touchTrace); assert.equal(await spoken(), 1); assert.equal(await p.evaluate(() => __speech.activeAtStart[0]), true); assert.ok(report.eventTrace.every(e => e.trusted)); assert.equal(await p.evaluate(() => visualViewport.scale), scale);
    assert.equal(await p.evaluate(() => __speech.utterances[0].text), await p.locator('#isi-bacaan p').first().textContent()); report.nativeTrace = await p.evaluate(() => __allTouchEvents); save(); assert.ok(await p.locator('#btn-stop-reading').isVisible());
    // Beberapa browser mengirim click/dblclick kompatibilitas setelah touchend.
    await p.locator('#isi-bacaan p').first().evaluate(el => { el.dispatchEvent(new MouseEvent('click', { bubbles: true, detail: 1 })); el.dispatchEvent(new MouseEvent('dblclick', { bubbles: true, detail: 2 })); });
    assert.equal(await spoken(), 1); assert.equal(await p.locator('#btn-baca').getAttribute('aria-pressed'), 'true');
    await p.locator('#btn-stop-reading').tap(); assert.equal(await p.locator('#btn-baca').getAttribute('aria-pressed'), 'false'); assert.equal(await p.locator('#btn-stop-reading').isVisible(), false);
  });
  await check('Ketukan berjeda panjang dan berpindah posisi tidak memulai suara', async () => {
    await fresh(); let target = await point(p); await p.touchscreen.tap(target.x, target.y); await p.waitForTimeout(550); await p.touchscreen.tap(target.x, target.y); assert.equal(await spoken(), 0);
    await fresh(); target = await point(p); await p.touchscreen.tap(target.x - 40, target.y); await p.waitForTimeout(80); await p.touchscreen.tap(target.x + 40, target.y); assert.equal(await spoken(), 0);
    await p.locator('#isi-bacaan p').nth(1).tap(); assert.equal(await spoken(), 0); assert.equal(await p.locator('#isi-bacaan p.aktif').getAttribute('data-paragraph'), '1');
  });
  await check('Menggulir dan membatalkan gestur tidak membacakan atau mengaktifkan fokus', async () => {
    await fresh(); const cdp = await context.newCDPSession(p); const target = await point(p, 2);
    await gesture(cdp, 'touchStart', [{ x: target.x, y: target.y, id: 1 }]);
    for (let delta = 15; delta <= 90; delta += 15) { await gesture(cdp, 'touchMove', [{ x: target.x, y: target.y - delta, id: 1 }]); await p.waitForTimeout(20); }
    await gesture(cdp, 'touchEnd', []); assert.equal(await spoken(), 0); assert.equal(await p.locator('body').evaluate(e => e.classList.contains('mode-fokus')), false);
    await fresh(); const next = await point(p); await gesture(cdp, 'touchStart', [{ x: next.x, y: next.y, id: 1 }]); await gesture(cdp, 'touchCancel', []); await p.touchscreen.tap(next.x, next.y); assert.equal(await spoken(), 0); await cdp.detach();
  });
  await check('Tekan lama dan dua jari tidak dianggap dua ketukan', async () => {
    await fresh(); const cdp = await context.newCDPSession(p); let target = await point(p);
    await gesture(cdp, 'touchStart', [{ x: target.x, y: target.y, id: 1 }]); await p.waitForTimeout(600); await gesture(cdp, 'touchEnd', []); assert.equal(await spoken(), 0);
    await fresh(); target = await point(p); await gesture(cdp, 'touchStart', [{ x: target.x - 25, y: target.y, id: 1 }, { x: target.x + 25, y: target.y, id: 2 }]); await gesture(cdp, 'touchEnd', []); await p.touchscreen.tap(target.x, target.y); assert.equal(await spoken(), 0); await cdp.detach();
  });
  await check('Ponsel pendek, orientasi mendatar, dan panel pengaturan saat suara berjalan', async () => {
    for (const [width, height] of [[320, 568], [844, 390], [390, 844]]) {
      await p.setViewportSize({ width, height }); await fresh(); const target = await point(p); await p.touchscreen.tap(target.x, target.y); await p.waitForTimeout(80); await p.touchscreen.tap(target.x, target.y); assert.equal(await spoken(), 1, width + ' ketukan ganda');
      assert.ok(await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth)); await p.locator('[data-settings-toggle]').tap(); await p.locator('[data-settings-close]').first().tap(); assert.equal(await p.locator('#btn-baca').getAttribute('aria-pressed'), 'true');
      await p.locator('#btn-stop-reading').tap(); assert.equal(await p.locator('#btn-baca').getAttribute('aria-pressed'), 'false');
    }
  });
  await check('Paragraf pendek di halaman tanpa gulir tetap dapat diketuk dua kali', async () => {
    await p.setViewportSize({ width: 390, height: 1200 }); await p.goto(base + 'teks-saya.php?tulis=1#tulis'); await p.locator('#judul').fill('Halaman pendek untuk ketuk ganda'); await p.locator('#isi_teks').fill('Kalimat pendek.'); await Promise.all([p.waitForURL('**/read.php?id=*'), p.getByRole('button', { name: 'Simpan & mulai membaca' }).click()]); await mockSpeech(p);
    const target = await point(p); await p.touchscreen.tap(target.x, target.y); await p.waitForTimeout(80); await p.touchscreen.tap(target.x, target.y); assert.equal(await spoken(), 1);
  });
  await check('Tidak ada galat JavaScript', async () => assert.deepEqual(report.errors, []));
})().catch(e => { report.failure = e.stack; console.error(e); process.exitCode = 1; }).finally(async () => { save(); if (browser) await browser.close(); });
