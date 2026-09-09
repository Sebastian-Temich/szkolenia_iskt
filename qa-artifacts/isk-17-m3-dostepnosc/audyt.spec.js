const { test, expect } = require('@playwright/test');
const fs = require('fs');

const base = 'http://localhost:8888';
const out = 'qa-artifacts/isk-17-m3-dostepnosc';
const viewports = [360, 768, 1440];

test.setTimeout(120000);

async function dynamicRoutes(page) {
  await page.goto(`${base}/trenerzy/`);
  const trainer = await page.locator('.iskt-trener-karta a').first().getAttribute('href');
  await page.goto(`${base}/aktualnosci/`);
  const article = await page.locator('.iskt-aktualnosc-karta a').first().getAttribute('href');

  return [
    ['home', `${base}/`],
    ['katalog', `${base}/szkolenia/`],
    ['szkolenie', `${base}/szkolenia/demo-formularz-esg-w-praktyce/`],
    ['trenerzy', `${base}/trenerzy/`],
    ['trener', trainer],
    ['aktualnosci', `${base}/aktualnosci/`],
    ['artykul', article],
    ['zgloszenie', `${base}/zgloszenie/`],
  ];
}

test('publiczne widoki: responsywność, nagłówki i pola', async ({ page }) => {
  fs.mkdirSync(out, { recursive: true });
  const routes = await dynamicRoutes(page);
  const report = [];
  const problems = [];

  for (const [name, url] of routes) {
    for (const width of viewports) {
      await page.setViewportSize({ width, height: 900 });
      const response = await page.goto(url, { waitUntil: 'networkidle' });
      expect(response.status(), `${name} ${width}: HTTP`).toBeLessThan(400);

      const result = await page.evaluate(() => {
        const headings = [...document.querySelectorAll('h1,h2,h3,h4,h5,h6')].map((h) => ({
          level: Number(h.tagName.slice(1)),
          text: (h.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 100),
        }));
        const skips = headings.slice(1).filter((h, index) => h.level > headings[index].level + 1);
        const unlabeled = [...document.querySelectorAll('input,select,textarea')]
          .filter((el) => !['hidden', 'submit', 'button'].includes(el.type))
          .filter((el) => {
            const label = el.labels && [...el.labels].some((item) => item.textContent.trim());
            return !label && !el.getAttribute('aria-label') && !el.getAttribute('aria-labelledby') && !el.title;
          })
          .map((el) => el.id || el.name || el.outerHTML.slice(0, 80));

        return {
          h1: headings.filter((h) => h.level === 1).length,
          skips,
          unlabeled,
          scrollWidth: document.documentElement.scrollWidth,
          clientWidth: document.documentElement.clientWidth,
        };
      });

      if (result.h1 !== 1) problems.push(`${name} ${width}: h1=${result.h1}`);
      if (result.skips.length) problems.push(`${name} ${width}: przeskoki=${JSON.stringify(result.skips)}`);
      if (result.unlabeled.length) problems.push(`${name} ${width}: bez etykiet=${result.unlabeled.join(', ')}`);
      if (result.scrollWidth > result.clientWidth + 1) problems.push(`${name} ${width}: overflow ${result.scrollWidth}/${result.clientWidth}`);
      await page.screenshot({ path: `${out}/${name}-${width}.png`, fullPage: true });
      report.push({ name, width, url, ...result });
    }
  }

  fs.writeFileSync(`${out}/responsywnosc.json`, `${JSON.stringify(report, null, 2)}\n`);
  expect(problems, problems.join('\n')).toEqual([]);
});

test('klawiatura: menu mobilne, przełącznik odbiorcy, filtry, karty i formularz', async ({ page }) => {
  await page.setViewportSize({ width: 360, height: 900 });
  await page.goto(base);

  const menu = page.locator('[data-iskt-nav-toggle]');
  await menu.focus();
  await expect(menu).toBeFocused();
  await page.keyboard.press('Enter');
  await expect(menu).toHaveAttribute('aria-expanded', 'true');

  const audience = page.locator('[data-iskt-switch] button').nth(1);
  await audience.focus();
  await page.keyboard.press('Enter');
  await expect(audience).toBeFocused();

  await page.goto(`${base}/szkolenia/`);
  for (const selector of ['#iskt-katalog-szukaj', '#iskt-katalog-kategoria', '#iskt-katalog-forma', '.iskt-filters button', '.iskt-card a']) {
    const item = page.locator(selector).first();
    await item.focus();
    await expect(item, `fokus: ${selector}`).toBeFocused();
    const focus = await item.evaluate((el) => {
      const style = getComputedStyle(el);
      return { style: style.outlineStyle, width: parseFloat(style.outlineWidth) };
    });
    expect(focus.style, `widoczny fokus: ${selector}`).not.toBe('none');
    expect(focus.width, `widoczny fokus: ${selector}`).toBeGreaterThan(0);
  }

  await page.goto(`${base}/zgloszenie/`);
  const controls = page.locator('form.iskt-form input:not([type="hidden"]), form.iskt-form select, form.iskt-form textarea, form.iskt-form button');
  expect(await controls.count()).toBeGreaterThan(5);
  for (let i = 0; i < await controls.count(); i += 1) {
    const control = controls.nth(i);
    if (!(await control.isVisible())) continue;
    await control.focus();
    await expect(control).toBeFocused();
  }
  await page.screenshot({ path: `${out}/klawiatura-formularz-360.png`, fullPage: true });
});
