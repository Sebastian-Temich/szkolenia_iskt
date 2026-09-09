/**
 * ISK-17 M2 zadanie 10 — teksty globalne edytowane z panelu.
 *
 * §4.7 wymaga, żeby każdy tekst redakcyjny widoczny dla odwiedzającego dał się
 * zmienić bez dotykania PHP. Test wykonuje tę ścieżkę tak, jak zrobi to właściciel:
 * otwiera ekran w panelu, zmienia napis, zapisuje i sprawdza stronę publiczną.
 *
 * Lekcja z M1 jest tutaj wprost zastosowana — sprawdzamy ścieżkę zapisu przez
 * formularz panelu, a nie przez `update_option()` z WP-CLI. WP-CLI ominąłby
 * `nonce`, kontrolę uprawnień i sanityzację, czyli dokładnie to, co ma działać.
 */
const { test, expect } = require('@playwright/test');

const base = 'http://localhost:8888';
const ekran = base + '/wp-admin/edit.php?post_type=iskt_szkolenie&page=iskt-teksty';

const DOMYSLNY_404 = 'Nie znaleźliśmy tej strony';
const WLASNY_404 = 'Tej strony u nas nie ma';

test.describe.configure({ mode: 'serial' });
test.setTimeout(60000);

async function zaloguj(page) {
  await page.goto(base + '/wp-login.php');

  if (await page.locator('#user_login').isVisible().catch(() => false)) {
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await page.click('#wp-submit');
    await page.waitForURL(/wp-admin/);
  }
}

/** Zapisuje ekran tekstów z podaną wartością jednego pola. */
async function zapiszTekst(page, id, wartosc) {
  await page.goto(ekran);
  await page.fill(id, wartosc);
  await page.click('#submit');
  await expect(page.locator('.notice-success')).toContainText('Teksty zostały zapisane');
}

test('ekran tekstów jest dostępny z menu Szkolenia', async ({ page }) => {
  await zaloguj(page);
  await page.goto(base + '/wp-admin/edit.php?post_type=iskt_szkolenie');

  const pozycja = page.locator('#adminmenu a', { hasText: 'Teksty serwisu' });
  await expect(pozycja).toHaveCount(1);

  await pozycja.click();
  await expect(page.locator('h1')).toContainText('Teksty serwisu');

  // Rejestr ma dać komplet grup — nie jeden ekran z kilkoma polami na próbę.
  for (const grupa of ['Dane kontaktowe', 'Katalog szkoleń', 'Formularz zgłoszeniowy']) {
    await expect(page.locator('h2', { hasText: grupa })).toHaveCount(1);
  }
});

test('zmiana napisu z panelu jest widoczna na stronie', async ({ page }) => {
  await zaloguj(page);

  // Stan wyjściowy: strona błędu pokazuje treść domyślną z rejestru.
  await page.goto(base + '/adres-ktorego-nie-ma-' + Date.now() + '/');
  await expect(page.locator('h1')).toContainText(DOMYSLNY_404);

  await zapiszTekst(page, '#iskt-tekst-blad404_tytul', WLASNY_404);

  await page.goto(base + '/adres-ktorego-nie-ma-' + Date.now() + '/');
  await expect(page.locator('h1')).toContainText(WLASNY_404);
});

test('wyczyszczenie pola przywraca treść domyślną', async ({ page }) => {
  await zaloguj(page);

  await zapiszTekst(page, '#iskt-tekst-blad404_tytul', '');

  await page.goto(base + '/adres-ktorego-nie-ma-' + Date.now() + '/');
  await expect(page.locator('h1')).toContainText(DOMYSLNY_404);
});

test('znaczniki HTML nie przechodzą przez zapis', async ({ page }) => {
  await zaloguj(page);

  await zapiszTekst(page, '#iskt-tekst-blad404_tytul', '<script>alert(1)</script>Bez skryptu');

  await page.goto(ekran);
  await expect(page.locator('#iskt-tekst-blad404_tytul')).toHaveValue('Bez skryptu');

  const strona = await page.goto(base + '/adres-ktorego-nie-ma-' + Date.now() + '/');
  expect(await strona.text()).not.toContain('<script>alert(1)</script>');

  // Sprzątamy po sobie, żeby test nie zostawiał zmienionego napisu w środowisku.
  await zapiszTekst(page, '#iskt-tekst-blad404_tytul', '');
});
