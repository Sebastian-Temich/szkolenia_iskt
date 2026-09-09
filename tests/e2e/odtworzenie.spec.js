/**
 * ISK-17 M3 zadanie 13 — zrzuty odtworzonej witryny.
 *
 * `tools/test-odtworzenia.sh` sprawdza, czy odtworzona witryna ODPOWIADA poprawnie.
 * Ten spec sprawdza, czy WYGLĄDA — bo eksport potrafi przenieść bazę i zwrócić 200
 * na każdym adresie, a jednocześnie zgubić plik zdjęcia albo arkusz stylów. Odpowiedź
 * z kodem 200 i pusta ramka w miejscu trenera to ten sam kod HTTP.
 *
 * Zrzuty trafiają do `qa-artifacts/isk-17-m3-eksport/` i są dowodem wymaganym przez
 * §11 pkt 12: pokazują odtworzoną witrynę, nie log importu.
 *
 * Wymaga witryny postawionej wcześniej przez `tools/test-odtworzenia.sh`; adres
 * bierze ze zmiennej ISKT_ADRES_ODTWORZENIA (domyślnie http://localhost:8890).
 */
const { test, expect } = require('@playwright/test');

const base = process.env.ISKT_ADRES_ODTWORZENIA || 'http://localhost:8890';
const shots = 'qa-artifacts/isk-17-m3-eksport';

test.describe.configure({ mode: 'serial' });
test.setTimeout(90000);

/**
 * Wchodzi pod adres odtworzonej witryny i pilnuje, żeby strona nie była pusta.
 *
 * `networkidle` zamiast `load`: obrazki dogrywają się po zdarzeniu `load`, a to
 * właśnie ich obecność zrzut ma udokumentować.
 */
async function otworz(page, sciezka) {
  const odpowiedz = await page.goto(base + sciezka, { waitUntil: 'networkidle' });
  expect(odpowiedz.status(), `${sciezka} odpowiada`).toBe(200);
}

test('katalog po odtworzeniu: lista, wyszukiwanie i brak wyników', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 1200 });

  await otworz(page, '/szkolenia/');
  await expect(page.getByRole('heading', { name: /Katalog szkoleń ISK-34/ })).toBeVisible();
  await page.screenshot({ path: `${shots}/katalog-1440.png`, fullPage: true });

  await otworz(page, '/szkolenia/?szukaj=termowizja');
  await expect(page.getByRole('link', { name: /Audyt energetyczny budynków/ }).first()).toBeVisible();
  await page.screenshot({ path: `${shots}/katalog-wyszukiwanie-1440.png`, fullPage: true });

  await otworz(page, '/szkolenia/?szukaj=zzzznieistniejaca');
  await expect(page.getByText(/Brak wyników ISK-34/)).toBeVisible();
  await page.screenshot({ path: `${shots}/katalog-brak-wynikow-1440.png`, fullPage: true });
});

test('szkolenie po odtworzeniu: trenerzy i terminy', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 1200 });

  await otworz(page, '/szkolenia/demo-audyt-energetyczny-budynkow/');

  // Powiązanie z jednej strony (§11 pkt 8).
  await expect(page.getByText(/Anna Kowalczyk/).first()).toBeVisible();

  // Termin zakończony nie ma prawa stać wśród nadchodzących (§11 pkt 5).
  const terminy = page.locator('.iskt-terminy');
  await expect(terminy).toBeVisible();
  await expect(terminy).not.toContainText('lipca');

  await page.screenshot({ path: `${shots}/szkolenie-1440.png`, fullPage: true });
});

test('profil trenera po odtworzeniu: zdjęcie z biblioteki i powiązania zwrotne', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 1200 });

  await otworz(page, '/trenerzy/demo-anna-kowalczyk/');

  // Powiązanie z drugiej strony — to ono najczęściej pada po niekompletnym eksporcie,
  // bo wylicza się zapytaniem po polu serializowanym, a nie osobną kolumną.
  await expect(page.getByRole('link', { name: /Audyt energetyczny budynków/ }).first()).toBeVisible();
  await expect(page.getByRole('link', { name: /Raportowanie ESG w praktyce/ }).first()).toBeVisible();

  // Zdjęcie MUSI się wczytać, a nie tylko mieć adres w atrybucie: obraz o zerowej
  // szerokości naturalnej to dokładnie ten przypadek, który zrzut pokazałby jako
  // pustą ramkę, a asercja na `src` przepuściłaby bez słowa.
  const zdjecie = page.locator('img[src*="demo-anna-kowalczyk-zdjecie"]').first();
  await expect(zdjecie).toBeVisible();
  expect(await zdjecie.evaluate((img) => img.naturalWidth)).toBeGreaterThan(0);

  await page.screenshot({ path: `${shots}/trener-1440.png`, fullPage: true });
});

test('strona główna i formularz po odtworzeniu', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 1200 });

  await otworz(page, '/');
  await expect(page.locator('[data-iskt-switch-panel]').first()).toBeVisible();
  await page.screenshot({ path: `${shots}/strona-glowna-1440.png`, fullPage: true });

  await otworz(page, '/zgloszenie/');
  // Blok, a nie sam formularz: §11 pkt 13 pyta, czy blok jest na stronie po imporcie.
  await expect(page.locator('.wp-block-iskt-formularz-zgloszeniowy form')).toBeVisible();
  await page.screenshot({ path: `${shots}/zgloszenie-1440.png`, fullPage: true });
});

test('odtworzona witryna na telefonie', async ({ page }) => {
  await page.setViewportSize({ width: 360, height: 800 });

  await otworz(page, '/szkolenia/');
  await page.screenshot({ path: `${shots}/katalog-360.png`, fullPage: true });

  await otworz(page, '/trenerzy/demo-anna-kowalczyk/');
  await page.screenshot({ path: `${shots}/trener-360.png`, fullPage: true });
});
