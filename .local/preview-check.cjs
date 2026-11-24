const { chromium } = require('./browser/node_modules/playwright-core');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

(async () => {
  const browser = await chromium.launch({ headless: true, executablePath: 'C:/Users/MSI/AppData/Local/ms-playwright/chromium-1234/chrome-win64/chrome.exe' });
  const report = { pages: [], errors: [] };
  try {
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const page = await context.newPage();
    page.on('pageerror', error => report.errors.push(error.message));
    page.on('response', response => {
      if (response.status() >= 400) report.errors.push(`${response.status()} ${response.url()}`);
    });
    const base = 'http://127.0.0.1:8000/';
    await page.goto(base);
    assert.equal(new URL(page.url()).pathname, '/login.php');
    await page.screenshot({ path: path.join(__dirname, 'runtime/login-desktop.png'), fullPage: true });
    report.pages.push('Anonymous users reach the login page');

    for (const [email, password, role] of [
      ['user@bacamudah.test', 'user12345', 'user'],
      ['admin@bacamudah.test', 'admin123', 'admin'],
    ]) {
      await page.goto(base + 'login.php');
      await page.locator('#email').fill(email);
      await page.locator('#password').fill(password);
      await Promise.all([
        page.waitForURL('**/index.php'),
        page.getByRole('button', { name: 'Masuk', exact: true }).click(),
      ]);
      assert.equal(await page.locator('h1').innerText(), 'Perpustakaan Umum');
      report.pages.push(`${role} demo login works`);
      if (role === 'user') {
        await page.screenshot({ path: path.join(__dirname, 'runtime/library-desktop.png'), fullPage: true });
        const readerLink = page.locator('a[href^="read.php?id="]').first();
        assert.ok(await readerLink.count(), 'Example text must be available');
        await readerLink.click();
        report.pages.push('Example reader opens');
        await page.screenshot({ path: path.join(__dirname, 'runtime/reader-desktop.png'), fullPage: true });
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto(base + 'index.php');
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
        assert.equal(overflow, false, 'Library should fit a phone viewport');
        await page.screenshot({ path: path.join(__dirname, 'runtime/library-mobile.png'), fullPage: true });
        report.pages.push('Phone library fits the viewport');
        await page.setViewportSize({ width: 1440, height: 1000 });
      } else {
        await page.goto(base + 'admin/texts.php');
        assert.equal(new URL(page.url()).pathname, '/admin/texts.php');
        report.pages.push('Admin text management opens');
      }
      await Promise.all([
        page.waitForURL('**/login.php'),
        page.getByRole('button', { name: /^Keluar/ }).click(),
      ]);
    }
    assert.deepEqual(report.errors, []);
    fs.writeFileSync(path.join(__dirname, 'runtime/preview-check.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
