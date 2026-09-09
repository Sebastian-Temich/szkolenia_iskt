/**
 * ISK-21 — kontrola mobilnej nawigacji przy 360 px, z JavaScriptem i bez niego.
 *
 * Sprawdzamy oba tory progressive enhancement:
 *   1. bez skryptu menu i podmenu są rozwinięte, widoczne i nie zasłaniają treści,
 *   2. ze skryptem przełącznik nadal zwija i rozwija panel oraz podmenu.
 */
const { test, expect } = require('@playwright/test');

const base = 'http://localhost:8888';

test('bez JavaScriptu menu i podmenu są rozwinięte i nie zasłaniają treści', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 360, height: 900 } });
  const page = await context.newPage();
  await page.goto(base + '/');

  const nav = page.locator('.iskt-site-nav');
  await page.screenshot({ path: 'qa-artifacts/isk-21/no-js-360.png', fullPage: true });

  await expect(nav).toBeVisible();
  await expect(page.locator('.iskt-site-nav__panel')).toBeVisible();
  await expect(page.locator('.iskt-menu .sub-menu')).toBeVisible();
  await expect(page.locator('.iskt-site-header__toggle')).toBeHidden();

  const links = await nav.locator('a').count();
  expect(links).toBeGreaterThanOrEqual(4);

  // Panel musi być w toku strony — inaczej nakłada się na treść (usterka ISK-20).
  const layout = await page.evaluate(() => {
    const panel = document.querySelector('.iskt-site-nav__panel');
    const header = document.querySelector('.iskt-site-header');
    const main = document.querySelector('#iskt-main');
    const p = panel.getBoundingClientRect();
    const h = header.getBoundingClientRect();
    const m = main.getBoundingClientRect();
    return {
      panelPosition: getComputedStyle(panel).position,
      panelDisplay: getComputedStyle(panel).display,
      navDisplay: getComputedStyle(document.querySelector('.iskt-site-nav')).display,
      panelWidth: Math.round(p.width),
      panelHeight: Math.round(p.height),
      panelBottom: Math.round(p.bottom),
      headerBottom: Math.round(h.bottom),
      mainTop: Math.round(m.top),
      overflowX: document.documentElement.scrollWidth > 360,
    };
  });

  expect(layout.panelPosition).toBe('static');
  expect(layout.panelHeight).toBeGreaterThan(0);
  expect(layout.panelBottom).toBeLessThanOrEqual(layout.headerBottom);
  expect(layout.mainTop).toBeGreaterThanOrEqual(layout.headerBottom);
  expect(layout.overflowX).toBe(false);
  console.log('NO_JS', JSON.stringify({ links, ...layout }));

  await context.close();
});

test('z JavaScriptem przełącznik nadal zwija i rozwija panel oraz podmenu', async ({ browser }) => {
  const context = await browser.newContext({ hasTouch: true, viewport: { width: 360, height: 900 } });
  const page = await context.newPage();
  await page.goto(base + '/');

  const toggle = page.locator('.iskt-site-header__toggle');
  const panel = page.locator('.iskt-site-nav__panel');

  await expect(toggle).toBeVisible();
  await expect(toggle).toHaveAttribute('aria-expanded', 'false');
  await expect(panel).toBeHidden();

  await toggle.tap();
  await expect(toggle).toHaveAttribute('aria-expanded', 'true');
  await expect(panel).toBeVisible();
  await page.screenshot({ path: 'qa-artifacts/isk-21/js-360-otwarte.png', fullPage: true });

  const expand = page.locator('.iskt-menu__expand').first();
  await expand.tap();
  await expect(expand).toHaveAttribute('aria-expanded', 'true');
  await expect(page.locator('.iskt-menu .sub-menu').first()).toBeVisible();

  await page.keyboard.press('Escape');
  await expect(toggle).toHaveAttribute('aria-expanded', 'false');
  await expect(panel).toBeHidden();
  await page.screenshot({ path: 'qa-artifacts/isk-21/js-360-zamkniete.png', fullPage: true });

  const state = await page.evaluate(() => {
    const header = document.querySelector('.iskt-site-header');
    return {
      headerPosition: getComputedStyle(header).position,
      hasJsClass: header.classList.contains('iskt-site-header--js'),
      panelPosition: getComputedStyle(document.querySelector('.iskt-site-nav__panel')).position,
    };
  });
  expect(state.hasJsClass).toBe(true);
  expect(state.headerPosition).toBe('sticky');
  expect(state.panelPosition).toBe('absolute');
  console.log('JS', JSON.stringify(state));

  await context.close();
});
