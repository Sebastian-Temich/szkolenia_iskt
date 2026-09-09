/**
 * ISK-17 M2 zadanie 8 — aktualności oczami odwiedzającego.
 *
 * §4.6 wymaga listy wpisów i szablonu artykułu; kształt widoków opisuje
 * `docs/PROJEKT-AKTUALNOSCI.md`, zaakceptowany przez ISKT 2026-09-09 (bramka 2).
 *
 * Test sprawdza to, co widać, a nie kod odpowiedzi. `/aktualnosci/` zwracało 200
 * także wtedy, gdy szablon listy w ogóle nie istniał — obsługiwał je wtedy
 * `index.php`. Dlatego każda asercja dotyczy treści albo klasy pochodzącej
 * z konkretnego szablonu zadania 8.
 */
const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const base = 'http://localhost:8888';
const shots = 'qa-artifacts/isk-17-aktualnosci';
const lista = base + '/aktualnosci/';

test.describe.configure({ mode: 'serial' });
test.setTimeout(120000);

/** Domyślne napisy z rejestru tekstów (grupa „Aktualności”). */
const CZYTAJ = 'Czytaj dalej';
const WSZYSTKIE = 'Wszystkie';
const POLECANE = 'Zobacz nasze szkolenia';
const NASTEPNY = 'Następny wpis';

const WPIS_STARSZY = 'Demo — Podsumowanie warsztatów z narzędzi generatywnych';
const WPIS_NOWSZY = 'Demo — Zmiany w naborach na dofinansowania szkoleń';
const KATEGORIA_NOWSZEGO = 'Demo — Dofinansowania';

/** Uruchamia WP-CLI w wp-env; jedna ponowna próba na wypadek zajętego kontenera. */
function wp(...argumenty) {
  const uruchom = () =>
    execFileSync('npx', ['@wordpress/env', 'run', 'cli', 'wp', ...argumenty], {
      cwd: process.cwd(),
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'pipe'],
    });

  try {
    return uruchom();
  } catch (blad) {
    execFileSync('sleep', ['10']);

    return uruchom();
  }
}

test.beforeAll(() => {
  const zrodlo = path.join(__dirname, 'dane-aktualnosci.php');
  const cel = path.join(process.cwd(), '.wp-env', 'uploads', 'iskt-dane-aktualnosci.php');

  fs.mkdirSync(path.dirname(cel), { recursive: true });
  fs.copyFileSync(zrodlo, cel);

  wp('eval-file', 'wp-content/uploads/iskt-dane-aktualnosci.php');

  fs.mkdirSync(shots, { recursive: true });
});

test('lista aktualności pokazuje wpisy w kartach i filtruje po kategorii', async ({ page }) => {
  await page.goto(lista);

  // Karta z template-parts/aktualnosc/karta.php, nie ogólna karta z index.php.
  const karty = page.locator('.iskt-aktualnosc-karta');
  await expect(karty.first()).toBeVisible();
  await expect(page.getByRole('link', { name: WPIS_STARSZY })).toBeVisible();
  await expect(page.getByRole('link', { name: WPIS_NOWSZY })).toBeVisible();

  // „Czytaj dalej” jest tekstem, nie odnośnikiem — karta ma mieć jeden cel dla klawiatury.
  await expect(page.getByRole('link', { name: CZYTAJ })).toHaveCount(0);
  await expect(karty.first().getByText(CZYTAJ)).toBeVisible();

  await page.setViewportSize({ width: 1440, height: 1200 });
  await page.screenshot({ path: `${shots}/lista-1440.png`, fullPage: true });

  for (const szerokosc of [360, 768]) {
    await page.setViewportSize({ width: szerokosc, height: 1200 });
    await page.screenshot({ path: `${shots}/lista-${szerokosc}.png`, fullPage: true });
  }

  await page.setViewportSize({ width: 1440, height: 1200 });

  /*
   * Filtr to zwykły odnośnik do archiwum kategorii, więc klikamy go jak
   * odwiedzający — nie doklejamy parametru do adresu.
   */
  const filtry = page.locator('.iskt-aktualnosci-filtry');
  await expect(filtry).toBeVisible();
  await filtry.getByRole('link', { name: KATEGORIA_NOWSZEGO }).click();

  await expect(page.getByRole('heading', { level: 1, name: KATEGORIA_NOWSZEGO })).toBeVisible();
  await expect(page.getByRole('link', { name: WPIS_NOWSZY })).toBeVisible();
  await expect(page.getByRole('link', { name: WPIS_STARSZY })).toHaveCount(0);

  // Bieżące zawężenie oznaczone dla czytnika ekranu, nie tylko kolorem.
  await expect(filtry.locator('[aria-current="page"]')).toHaveText(KATEGORIA_NOWSZEGO);

  await page.screenshot({ path: `${shots}/kategoria-1440.png`, fullPage: true });

  // Powrót do „Wszystkie” przywraca pełną listę.
  await filtry.getByRole('link', { name: WSZYSTKIE }).click();
  await expect(page.getByRole('link', { name: WPIS_STARSZY })).toBeVisible();
});

test('artykuł ma ścieżkę powrotu, sąsiadów i sekcję ze szkoleniami', async ({ page }) => {
  await page.goto(lista);
  await page.getByRole('link', { name: WPIS_STARSZY }).click();

  await expect(page.getByRole('heading', { level: 1, name: WPIS_STARSZY })).toBeVisible();
  await expect(page.locator('.iskt-prose').getByRole('heading', { name: 'Co zabraliśmy ze sobą' })).toBeVisible();

  // Ścieżka nawigacji prowadzi do listy i do kategorii wpisu (PROJEKT-AKTUALNOSCI §4).
  const sciezka = page.locator('.iskt-breadcrumb');
  await expect(sciezka.getByRole('link').first()).toHaveAttribute('href', /\/aktualnosci\/?$/);

  // §4.6 nie wymienia autora wśród pól — projekt świadomie go nie pokazuje.
  await expect(page.locator('.iskt-entry__meta')).not.toContainText('admin');

  const sasiedzi = page.locator('.iskt-aktualnosc__sasiedzi');
  await expect(sasiedzi).toBeVisible();
  await expect(sasiedzi.getByText(NASTEPNY)).toBeVisible();

  const polecane = page.locator('.iskt-aktualnosc-polecane');
  await expect(polecane.getByRole('heading', { name: POLECANE })).toBeVisible();
  await expect(polecane.locator('.iskt-card').first()).toBeVisible();

  await page.setViewportSize({ width: 1440, height: 1200 });
  await page.screenshot({ path: `${shots}/artykul-1440.png`, fullPage: true });

  for (const szerokosc of [360, 768]) {
    await page.setViewportSize({ width: szerokosc, height: 1200 });
    await page.screenshot({ path: `${shots}/artykul-${szerokosc}.png`, fullPage: true });
  }
});

test('adres artykułu działa po bezpośrednim wejściu i po odświeżeniu (§11 pkt 6)', async ({ page }) => {
  await page.goto(lista);

  const adres = await page.getByRole('link', { name: WPIS_NOWSZY }).getAttribute('href');

  await page.goto(adres);
  await expect(page.getByRole('heading', { level: 1, name: WPIS_NOWSZY })).toBeVisible();

  await page.reload();
  await expect(page.getByRole('heading', { level: 1, name: WPIS_NOWSZY })).toBeVisible();

  /*
   * „Wstecz” ma wrócić na listę, a „dalej” — z powrotem na artykuł. W prototypie
   * obie te czynności gubiły widok, bo adres nie zmieniał się przy przejściu.
   * Odświeżenie nie dokłada wpisu do historii, więc sprawdzamy parę wstecz/dalej,
   * a nie samo wstecz.
   */
  await page.goBack();
  await expect(page).toHaveURL(/\/aktualnosci\/?$/);

  await page.goForward();
  await expect(page.getByRole('heading', { level: 1, name: WPIS_NOWSZY })).toBeVisible();
});

test('nawigacja artykułu działa z klawiatury i nie zostawia odnośników „#” (§11 pkt 9 i 10)', async ({ page }) => {
  await page.goto(lista);
  await page.getByRole('link', { name: WPIS_STARSZY }).click();

  // Żaden odnośnik widoku nie prowadzi w puste miejsce.
  await expect(page.locator('main a[href="#"]')).toHaveCount(0);

  const powrot = page.locator('.iskt-breadcrumb a').first();
  await powrot.focus();
  await expect(powrot).toBeFocused();

  await page.keyboard.press('Enter');
  await expect(page).toHaveURL(/\/aktualnosci\/?$/);
});
