const { test, expect } = require('@playwright/test');

const base = 'http://localhost:8888';

test.use({ hasTouch: true });

test('mobile menu, touch submenu and visible focus', async ({ page }) => {
  await page.setViewportSize({ width: 360, height: 900 });
  await page.goto(base + '/');
  const menu = page.locator('.iskt-site-header__toggle');
  await expect(menu).toHaveAttribute('aria-expanded', 'false');
  await menu.tap();
  await expect(menu).toHaveAttribute('aria-expanded', 'true');
  const submenu = page.locator('.iskt-menu__expand').first();
  await submenu.tap();
  await expect(submenu).toHaveAttribute('aria-expanded', 'true');
  await page.keyboard.press('Escape');
  await expect(menu).toHaveAttribute('aria-expanded', 'false');

  await page.keyboard.press('Tab');
  const focusEvidence = await page.evaluate(() => {
    const el = document.activeElement;
    const style = getComputedStyle(el);
    return { tag: el.tagName, text: el.textContent.trim(), outline: style.outline, boxShadow: style.boxShadow };
  });
  expect(focusEvidence.outline !== 'none' || focusEvidence.boxShadow !== 'none').toBeTruthy();
  console.log('FOCUS', JSON.stringify(focusEvidence));
});

test('navigation stays expanded without JavaScript', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 360, height: 900 } });
  const page = await context.newPage();
  await page.goto(base + '/');
  const nav = page.locator('.iskt-site-nav');
  await page.screenshot({ path: 'qa-artifacts/isk-20/no-js-360.png', fullPage: true });
  await expect(nav).toBeVisible();
  const links = await nav.locator('a').count();
  expect(links).toBeGreaterThanOrEqual(4);
  console.log('NO_JS_LINKS', links);
  await context.close();
});

test('fallback without backdrop-filter remains opaque and readable', async ({ page }) => {
  await page.goto(base + '/');
  await page.addStyleTag({ content: '.iskt-site-header{backdrop-filter:none!important;-webkit-backdrop-filter:none!important}' });
  const state = await page.locator('.iskt-site-header').evaluate((el) => {
    const s = getComputedStyle(el);
    return { backgroundColor: s.backgroundColor, backdropFilter: s.backdropFilter || s.webkitBackdropFilter };
  });
  expect(state.backgroundColor).not.toBe('rgba(0, 0, 0, 0)');
  await page.screenshot({ path: 'qa-artifacts/isk-20/no-backdrop-1440.png', fullPage: true });
  console.log('NO_BACKDROP', JSON.stringify(state));
});

test('key palette contrast ratios meet WCAG AA', async ({ page }) => {
  await page.goto(base + '/');
  const samples = await page.evaluate(() => {
    const rgb = (v) => (v.match(/[\d.]+/g) || []).slice(0, 3).map(Number);
    const lum = (v) => {
      const c = rgb(v).map(x => x / 255).map(x => x <= .03928 ? x / 12.92 : Math.pow((x + .055) / 1.055, 2.4));
      return .2126*c[0] + .7152*c[1] + .0722*c[2];
    };
    const ratio = (a,b) => { const x=lum(a), y=lum(b); return (Math.max(x,y)+.05)/(Math.min(x,y)+.05); };
    return ['body','.iskt-card','.iskt-site-footer'].map(sel => {
      const el=document.querySelector(sel), s=getComputedStyle(el);
      return { selector:sel, color:s.color, background:s.backgroundColor, ratio:ratio(s.color,s.backgroundColor) };
    });
  });
  for (const sample of samples) expect(sample.ratio).toBeGreaterThanOrEqual(4.5);
  console.log('CONTRAST', JSON.stringify(samples));
});
