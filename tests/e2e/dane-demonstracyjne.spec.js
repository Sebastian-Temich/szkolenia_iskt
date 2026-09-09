/**
 * ISK-17 M3 zadanie 12 — dane demonstracyjne: oznaczenie i usunięcie jednym działaniem.
 *
 * §9 zabrania zostawiania niezatwierdzonych treści jako oferty ISKT, §8 wymaga, żeby
 * właściciel mógł je usunąć. Ten test przechodzi tę ścieżkę tak, jak przejdzie ją
 * właściciel po migracji: zakłada WŁASNE szkolenie, kasuje dane demonstracyjne
 * jednym zgłoszeniem formularza i sprawdza, że jego wpis stoi nietknięty.
 *
 * Test jest destrukcyjny z założenia — kasuje wszystko, co nosi oznaczenie. Dlatego
 * sam zasiewa dane przed przebiegiem i odtwarza je po nim: środowisko robocze dzielą
 * inne zadania ISK-17 i nie może zostać po nas puste.
 *
 * Uruchomienie:
 *   PW=$(for d in ~/.npm/_npx/*\/node_modules; do [ -d "$d/@playwright/test" ] && echo "$d" && break; done)
 *   NODE_PATH=$PW npx @playwright/test test tests/e2e/dane-demonstracyjne.spec.js --reporter=line
 */
const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const {
  base,
  zaloguj,
  usunPozostalosci,
  zamknijPowitanie,
  wpiszTytul,
  wpiszTresc,
  opublikujBlokowy,
} = require('./panel');

const shots = 'qa-artifacts/isk-17-m3-demo';

test.describe.configure({ mode: 'serial' });
test.setTimeout(180000);

/**
 * Wpis WŁAŚCICIELA. Celowo bez prefiksu „Demo —”: to on ma przeżyć sprzątanie,
 * a znacznik uruchomienia pozwala powtórzyć test na tym samym środowisku.
 */
const znak = String(Date.now()).slice(-5);
const WLASNE = `Szkolenie wlasciciela ${znak}`;
const WLASNY_OPIS = 'Wpis zalozony przez wlasciciela. Nie jest danymi demonstracyjnymi.';

/** Kategoria startowa z §4.2 — słownik właściciela, nie dane pokazowe. */
const KATEGORIA_WLASNA = 'ESG i zrównoważony rozwój';

const EKRAN_DEMO = `${base}/wp-admin/edit.php?post_type=iskt_szkolenie&page=iskt-dane-demonstracyjne`;
const PLAKIETKA = 'Dane demonstracyjne';

function wp(...argumenty) {
  return execFileSync('npx', ['@wordpress/env', 'run', 'cli', 'wp', ...argumenty], {
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}

/** Zasiewa komplet danych demonstracyjnych i domyka oznaczenie dla danych z panelu. */
function zasiej() {
  wp('eval-file', 'wp-content/iskt-tests/e2e/dane-katalogu.php');
  wp('eval-file', 'wp-content/iskt-tests/e2e/dane-aktualnosci.php');
  wp('eval-file', 'wp-content/iskt-tests/e2e/dane-formularza.php');
  wp('eval-file', 'wp-content/iskt-tests/e2e/oznacz-demo.php');
}

test.beforeAll(async ({ browser }) => {
  zasiej();

  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    await zaloguj(page);
    await usunPozostalosci(page, 'iskt_szkolenie', 'Szkolenie wlasciciela');
  } finally {
    await context.close();
  }
});

/*
 * Odtworzenie środowiska. Bez tego kolejne zadanie ISK-17 zastałoby pustą bazę
 * i wzięłoby ją za usterkę własnej zmiany.
 */
test.afterAll(() => {
  zasiej();
});

test('właściciel zakłada własne szkolenie', async ({ page }) => {
  await zaloguj(page);
  await page.goto(`${base}/wp-admin/post-new.php?post_type=iskt_szkolenie`);
  await zamknijPowitanie(page);

  await wpiszTytul(page, WLASNE);
  await wpiszTresc(page, WLASNY_OPIS);
  await page.locator('[id="iskt-pole-_iskt_czas_trwania"]').fill('2 dni (16 godzin)');

  const adres = await opublikujBlokowy(page);

  expect(adres).toContain('/szkolenia/');
});

test('lista w panelu odróżnia wpis demonstracyjny od własnego', async ({ page }) => {
  await zaloguj(page);
  await page.goto(`${base}/wp-admin/edit.php?post_type=iskt_szkolenie&s=${encodeURIComponent('Demo — Raportowanie ESG')}`);

  const demonstracyjny = page.locator('#the-list tr').first();

  // §9: wpis demonstracyjny ma być rozpoznawalny BEZ otwierania go.
  await expect(demonstracyjny).toContainText(PLAKIETKA);

  await page.goto(`${base}/wp-admin/edit.php?post_type=iskt_szkolenie&s=${encodeURIComponent(WLASNE)}`);

  const wlasny = page.locator('#the-list tr').first();

  await expect(wlasny).toContainText(WLASNE);
  await expect(wlasny).not.toContainText(PLAKIETKA);

  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(`${base}/wp-admin/edit.php?post_type=iskt_szkolenie`);
  await page.screenshot({ path: `${shots}/lista-plakietki-1440.png`, fullPage: true });
});

test('ekran spisu wymienia dane demonstracyjne i pomija wpis właściciela', async ({ page }) => {
  await zaloguj(page);
  await page.goto(EKRAN_DEMO);

  await expect(page.locator('h1')).toContainText('Dane demonstracyjne');

  const spis = page.locator('.iskt-demo-spis');

  await expect(spis.first()).toContainText('Demo — Raportowanie ESG w praktyce');
  await expect(page.locator('.wrap.iskt-demo')).toContainText('Demo — Wydarzenia ISKT');

  // Wpis właściciela nie może się znaleźć na liście do skasowania.
  await expect(page.locator('.wrap.iskt-demo')).not.toContainText(WLASNE);

  await page.setViewportSize({ width: 1440, height: 900 });
  await page.screenshot({ path: `${shots}/spis-1440.png`, fullPage: true });
});

test('jedno zgłoszenie formularza usuwa wszystkie dane demonstracyjne', async ({ page }) => {
  await zaloguj(page);
  await page.goto(EKRAN_DEMO);

  await page.locator('input[name="potwierdzenie"]').check();
  await page.getByRole('button', { name: 'Usuń dane demonstracyjne' }).click();

  // Ekran pokazuje dwa komunikaty naraz: podsumowanie akcji i „nie ma już czego usuwać”.
  await expect(page.locator('.notice-success').first()).toContainText('Usunięto dane demonstracyjne');

  await page.screenshot({ path: `${shots}/po-usunieciu-1440.png`, fullPage: true });

  // Powtórne wejście: spis pusty, bo nie ma już czego wymieniać.
  await page.goto(EKRAN_DEMO);
  await expect(page.locator('.wrap.iskt-demo')).toContainText('Nie ma żadnych danych demonstracyjnych');
  await expect(page.locator('.iskt-demo-spis')).toHaveCount(0);
});

test('wpis właściciela, słowniki i teksty serwisu przetrwały sprzątanie', async ({ page }) => {
  await zaloguj(page);

  // 1. Szkolenie właściciela dalej opublikowane.
  await page.goto(`${base}/wp-admin/edit.php?post_type=iskt_szkolenie&s=${encodeURIComponent(WLASNE)}`);
  await expect(page.locator('#the-list')).toContainText(WLASNE);
  await expect(page.locator('#the-list tr').first()).not.toContainText(PLAKIETKA);

  // 2. Kategoria startowa właściciela na miejscu, kategoria demonstracyjna zniknęła.
  await page.goto(`${base}/wp-admin/edit-tags.php?taxonomy=iskt_kategoria&post_type=iskt_szkolenie`);
  await expect(page.locator('#the-list')).toContainText(KATEGORIA_WLASNA);

  await page.goto(`${base}/wp-admin/edit-tags.php?taxonomy=category`);
  await expect(page.locator('#the-list')).not.toContainText('Demo — Wydarzenia ISKT');

  // 3. Rejestr tekstów to osobny mechanizm — sprzątanie wpisów go nie dotyka.
  await page.goto(`${base}/wp-admin/edit.php?post_type=iskt_szkolenie&page=iskt-teksty`);
  await expect(page.locator('h1')).toContainText('Teksty serwisu');
  await expect(page.locator('#iskt-tekst-kontakt_email')).toHaveCount(1);
});

test('widoki publiczne stoją bez błędów i bez pustych sekcji', async ({ page }) => {
  await page.context().clearCookies();

  for (const [nazwa, adres] of [
    ['strona-glowna', `${base}/`],
    ['katalog', `${base}/szkolenia/`],
    ['trenerzy', `${base}/trenerzy/`],
  ]) {
    const odpowiedz = await page.goto(adres);

    expect(odpowiedz.status()).toBe(200);
    await expect(page.locator('body')).not.toContainText('Fatal error');

    await page.setViewportSize({ width: 1440, height: 900 });
    await page.screenshot({ path: `${shots}/${nazwa}-po-sprzataniu-1440.png`, fullPage: true });
  }

  // Strona główna ma pozostać stroną główną, a nie listą wpisów (przełącznik §4.1).
  await page.goto(`${base}/`);
  await expect(page.locator('[data-iskt-switch-panel]').first()).toHaveCount(1);

  // Katalog bez danych demonstracyjnych pokazuje wpis właściciela, a nie pustkę.
  await page.goto(`${base}/szkolenia/`);
  await expect(page.locator('body')).toContainText(WLASNE);
});
