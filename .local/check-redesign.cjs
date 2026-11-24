const { chromium } = require('./browser/node_modules/playwright-core');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const base = 'http://127.0.0.1:8000/';
const artifacts = path.join(__dirname, 'runtime/redesign');
fs.mkdirSync(artifacts, { recursive: true });
const report = { checks: [], accessibility: [], errors: [] };
const email = `uji-ui-${Date.now()}@bacamudah.test`;
let page;
async function go(url) { await page.goto(base + url, { waitUntil: 'networkidle' }); }
async function submit(locator) { await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), locator.click()]); }
async function note(message) { report.checks.push(message); console.log('PASS ' + message); }
async function noOverflow(label) {
  const dimensions = await page.evaluate(() => ({ page: document.documentElement.scrollWidth, viewport: window.innerWidth }));
  assert.ok(dimensions.page <= dimensions.viewport, `${label}: overflow ${JSON.stringify(dimensions)}`);
}
async function capture(label, audit = false) {
  await page.evaluate(() => document.fonts.ready);
  await noOverflow(label);
  await page.screenshot({ path: path.join(artifacts, label + '.png'), fullPage: true });
  if (audit) {
    await page.addScriptTag({ path: require.resolve('./browser/node_modules/axe-core/axe.min.js') });
    const result = await page.evaluate(async () => {
      const r = await axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21aa'] } });
      return r.violations.map(v => ({ id: v.id, impact: v.impact, description: v.description, nodes: v.nodes.map(n => ({ target: n.target, summary: n.failureSummary })) }));
    });
    report.accessibility.push({ page: label, violations: result });
    console.log('AXE ' + label + ': ' + result.length + ' violations');
  }
}
async function login(user, password) {
  await go('login.php');
  await page.locator('#email').fill(user);
  await page.locator('#password').fill(password);
  await submit(page.locator('.auth-form [type="submit"]'));
  assert.match(page.url(), /index\.php$/);
}
async function logout() {
  await page.locator('.account-menu summary').click();
  await submit(page.getByRole('button', { name: 'Keluar akun' }));
  assert.match(page.url(), /login\.php$/);
}

(async () => {
  const browser = await chromium.launch({ headless: true, executablePath: 'C:/Users/MSI/AppData/Local/ms-playwright/chromium-1234/chrome-win64/chrome.exe' });
  try {
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce' });
    page = await context.newPage();
    page.setDefaultTimeout(20000);
    page.setDefaultNavigationTimeout(45000);
    page.on('pageerror', error => report.errors.push(error.message));
    page.on('response', response => { if (response.status() >= 500) report.errors.push(`${response.status()} ${response.url()}`); });
    await go('login.php');
    await capture('01-login-desktop', true);
    await page.getByRole('button', { name: 'Sebagai pembaca' }).click();
    assert.equal(await page.locator('#email').inputValue(), 'user@bacamudah.test');
    await submit(page.locator('.auth-form [type="submit"]'));
    await note('Demo-account button fills credentials and login succeeds');
    await capture('02-library-desktop', true);
    assert.ok(await page.locator('.kartu-bacaan').count() >= 3);
    await page.locator('#q').fill('Tips Membaca');
    await submit(page.getByRole('button', { name: /^Cari/ }));
    assert.equal(await page.locator('.kartu-bacaan').count(), 1);
    await page.locator('#kategori').selectOption('3');
    await page.locator('#q').fill('');
    await submit(page.getByRole('button', { name: /^Cari/ }));
    assert.equal(await page.locator('.kartu-bacaan').count(), 1);
    await page.locator('#q').fill('tidak-ada-judul-seperti-ini');
    await submit(page.getByRole('button', { name: /^Cari/ }));
    assert.equal(await page.locator('.empty-state').count(), 1);
    await note('Search, category filter, and empty results work');

    await go('read.php?id=1');
    await capture('03-reader-desktop', true);
    await page.locator('#btn-fokus').click();
    assert.equal(await page.locator('#btn-fokus').getAttribute('aria-pressed'), 'true');
    await page.locator('#focus-next').click();
    assert.equal(await page.locator('.isi-bacaan p.aktif').getAttribute('data-paragraph'), '1');
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('#btn-fokus').getAttribute('aria-pressed'), 'false');
    await page.locator('[data-reading-preset="lapang"]').click();
    assert.equal(await page.locator('#ukuran').inputValue(), '22');
    await page.locator('input[name="tema"][value="kontras"]').check();
    await submit(page.locator('#form-atur [type="submit"]'));
    assert.equal(await page.locator('body').getAttribute('data-tema'), 'kontras');
    assert.equal(await page.locator('#ukuran').inputValue(), '22');
    await capture('04-reader-high-contrast', true);
    await page.locator('#btn-reset').click();
    await submit(page.locator('#form-atur [type="submit"]'));
    await note('Focus navigation, reading presets, theme preview and persisted settings work');

    await page.setViewportSize({ width: 390, height: 844 });
    await capture('05-reader-mobile');
    await page.locator('[data-settings-toggle]').click();
    assert.equal(await page.locator('[data-settings-toggle]').getAttribute('aria-expanded'), 'true');
    await capture('06-settings-mobile', true);
    await page.keyboard.press('Shift+Tab');
    assert.equal(await page.evaluate(() => document.activeElement.id), 'btn-reset');
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('[data-settings-toggle]').getAttribute('aria-expanded'), 'false');
    await go('index.php');
    await capture('07-library-mobile', true);
    await page.locator('[data-nav-toggle]').click();
    assert.equal(await page.locator('[data-nav-toggle]').getAttribute('aria-expanded'), 'true');
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('[data-nav-toggle]').getAttribute('aria-expanded'), 'false');
    await page.setViewportSize({ width: 320, height: 760 });
    await noOverflow('320px library');
    await go('read.php?id=1');
    await noOverflow('320px reader');
    await note('390px and 320px layouts, modal focus trap, Escape, and mobile navigation work');
    await logout();
    await page.setViewportSize({ width: 1440, height: 1000 });

    await go('register.php');
    await page.locator('#nama').fill('Pembaca Uji');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill('RuangBacaUji123');
    await page.locator('#password2').fill('berbeda');
    await submit(page.locator('.auth-form [type="submit"]'));
    assert.ok(await page.locator('.pesan-galat').isVisible());
    await page.locator('#password').fill('RuangBacaUji123');
    await page.locator('#password2').fill('RuangBacaUji123');
    await submit(page.locator('.auth-form [type="submit"]'));
    assert.match(page.url(), /login\.php$/);
    await login(email, 'RuangBacaUji123');
    await go('teks-saya.php');
    await capture('08-private-texts-desktop', true);
    const title = 'Catatan Uji Antarmuka ' + Date.now();
    await page.locator('#judul').fill(title);
    await page.locator('#isi_teks').fill('Paragraf pertama untuk membaca dengan nyaman.\n\nParagraf kedua untuk mencoba mode fokus.');
    assert.equal(await page.locator('#editor-count').textContent(), '12 kata');
    await submit(page.getByRole('button', { name: 'Simpan & mulai membaca' }));
    assert.equal(await page.locator('#judul-teks').textContent(), title);
    const privateId = new URL(page.url()).searchParams.get('id');
    await submit(page.getByRole('button', { name: 'Tambah bookmark', exact: true }));
    await go('bookmarks.php');
    assert.ok(await page.getByRole('heading', { name: title }).isVisible());
    await capture('09-bookmarks-desktop', true);
    await go('teks-edit.php?id=' + privateId);
    await page.locator('#judul').fill(title + ' Diperbarui');
    await submit(page.getByRole('button', { name: 'Simpan perubahan' }));
    assert.equal(await page.locator('#judul-teks').textContent(), title + ' Diperbarui');
    await go('teks-saya.php');
    await page.getByRole('button', { name: 'Hapus ' + title + ' Diperbarui', exact: true }).click();
    await page.getByRole('button', { name: 'Tetap simpan' }).click();
    assert.ok(await page.getByRole('heading', { name: title + ' Diperbarui' }).isVisible());
    await page.getByRole('button', { name: 'Hapus ' + title + ' Diperbarui', exact: true }).click();
    await submit(page.getByRole('button', { name: 'Ya, hapus' }));
    assert.equal(await page.getByRole('heading', { name: title + ' Diperbarui' }).count(), 0);
    await go('bookmarks.php');
    assert.equal(await page.locator('.kartu-bacaan').count(), 0);
    await note('Registration validation, private-text create/edit/delete, delete cancellation and bookmarks work');
    await go('admin/texts.php');
    assert.match(page.url(), /index\.php$/);
    await note('A regular user cannot access admin pages');
    await logout();
    await login('admin@bacamudah.test', 'admin123');
    await go('admin/texts.php');
    await capture('10-admin-desktop', true);
    await go('admin/categories.php');
    await page.setViewportSize({ width: 390, height: 844 });
    await capture('11-admin-mobile');
    await logout();
    await capture('12-login-mobile', true);
    await note('Admin screens and mobile login render without horizontal overflow');
    assert.deepEqual(report.errors, []);
    console.log('Browser checks complete.');
  } catch (error) {
    report.failure = error.stack;
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    throw error;
  } finally {
    report.testAccount = email;
    fs.writeFileSync(path.join(artifacts, 'report.json'), JSON.stringify(report, null, 2));
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
