/**
 * ISK-17 M2 zadanie 7 — trenerzy: lista, profil i spójność powiązań.
 *
 * §4.5 wymaga profilu trenera pod trwałym adresem, listy trenerów i listy szkoleń
 * prowadzonych przez trenera; §11 pkt 8 — żeby powiązanie było widoczne z obu
 * stron. Test wykonuje tę ścieżkę przez panel, bo tylko to dowodzi, że właściciel
 * sam zbuduje zespół trenerski.
 *
 * Sprawdzamy też stany, które łatwo wziąć za usterkę, a usterką nie są: trener bez
 * zdjęcia, trener bez szkoleń i szkolenie wycofane z publikacji.
 *
 * Test jest odtwarzalny — dane niosą znacznik uruchomienia, więc drugie wykonanie
 * nie zderza się z pierwszym.
 */
const { test, expect } = require('@playwright/test');
const {
  base,
  zaloguj,
  usunPozostalosci,
  zamknijPowitanie,
  wpiszTytul,
  wpiszTresc,
  wpiszZajawke,
  opublikujBlokowy,
} = require('./panel');

const shots = 'qa-artifacts/isk-17-m2-trenerzy';

test.describe.configure({ mode: 'serial' });
test.setTimeout(120000);

/** Dane jawnie demonstracyjne — §9 zabrania udawania zatwierdzonej oferty. */
const znak = String(Date.now()).slice(-5);

const TRENER = `Demo — Barbara Nowak ${znak}`;
const TRENER_ROLA = 'Trenerka zarzadzania zmiana';
const TRENER_OPIS = 'Prowadzi warsztaty dla zespolow wdrazajacych nowe narzedzia.';
const TRENER_BIOGRAFIA = 'Od dwunastu lat pracuje z zespolami produkcyjnymi przy zmianie sposobu pracy.';
const DOSWIADCZENIE = '90 dni warsztatow rocznie';
const WYKSZTALCENIE = 'Certyfikat Prince2 Foundation (demonstracyjny)';
const SPECJALIZACJA = 'Zarzadzanie zmiana w zespole';

const TRENER_NOWY = `Demo — Czeslaw Dopiero ${znak}`;
const SZKOLENIE = `Demo — Zarzadzanie zmiana w zespole ${znak}`;

/** Napisy domyślne z rejestru tekstów (grupa „Trenerzy”). */
const NAGLOWEK_DOSWIADCZENIE = 'Doświadczenie';
const NAGLOWEK_WYKSZTALCENIE = 'Wykształcenie i certyfikaty';
const NAGLOWEK_SPECJALIZACJE = 'Specjalizacje';
const NAGLOWEK_SZKOLENIA = 'Szkolenia prowadzone przez trenera';
const BRAK_SZKOLEN = 'Ten trener nie ma jeszcze przypisanych szkoleń w katalogu.';

let adresTrenera = '';
let adresNowego = '';
let adresSzkolenia = '';

/** Nazwy bez znacznika — po nich znajdujemy dane z wcześniejszych przebiegów. */
const RDZENIE = [
  ['iskt_szkolenie', 'Demo — Zarzadzanie zmiana'],
  ['iskt_trener', 'Demo — Barbara Nowak'],
  ['iskt_trener', 'Demo — Czeslaw Dopiero'],
];

test.beforeAll(async ({ browser }) => {
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    await zaloguj(page);

    for (const [typ, tytul] of RDZENIE) {
      await usunPozostalosci(page, typ, tytul);
    }
  } finally {
    await context.close();
  }
});

test('właściciel zakłada profil trenera z kompletem pól', async ({ page }) => {
  await zaloguj(page);
  await page.goto(base + '/wp-admin/post-new.php?post_type=iskt_trener');
  await zamknijPowitanie(page);

  await wpiszTytul(page, TRENER);
  await wpiszTresc(page, TRENER_BIOGRAFIA);
  await wpiszZajawke(page, TRENER_OPIS);

  // Skrzynka metadanych renderuje się pod edytorem — poza kanwą, w zwykłym DOM strony.
  await page.locator('[id="iskt-pole-_iskt_rola"]').fill(TRENER_ROLA);
  await page.locator('[id="iskt-pole-_iskt_doswiadczenie"]').fill(`${DOSWIADCZENIE}\nWdrozenia w 40 firmach`);
  await page.locator('[id="iskt-pole-_iskt_wyksztalcenie"]').fill(`${WYKSZTALCENIE}\nStudia podyplomowe z psychologii pracy`);
  await page.locator('[id="iskt-pole-_iskt_specjalizacje"]').fill(`${SPECJALIZACJA}\nKomunikacja w zespole rozproszonym`);

  adresTrenera = await opublikujBlokowy(page);
  expect(adresTrenera).toContain('/trenerzy/');
});

test('właściciel zakłada trenera bez zdjęcia i bez szkoleń', async ({ page }) => {
  await zaloguj(page);
  await page.goto(base + '/wp-admin/post-new.php?post_type=iskt_trener');
  await zamknijPowitanie(page);

  await wpiszTytul(page, TRENER_NOWY);
  await page.locator('[id="iskt-pole-_iskt_rola"]').fill('Trener w przygotowaniu');

  adresNowego = await opublikujBlokowy(page);
});

test('właściciel przypisuje trenera do szkolenia', async ({ page }) => {
  await zaloguj(page);
  await page.goto(base + '/wp-admin/post-new.php?post_type=iskt_szkolenie');
  await zamknijPowitanie(page);

  await wpiszTytul(page, SZKOLENIE);

  await page.locator('[id="iskt-pole-_iskt_czas_trwania"]').fill('1 dzien (8 godzin)');

  /*
   * Powiązanie zapisujemy WYŁĄCZNIE tutaj (ADR-001 §2.4). Profil trenera nie ma
   * własnego pola „moje szkolenia” — wylicza je zapytaniem zwrotnym. Gdyby istniały
   * dwie kopie relacji, ten test przechodziłby, a widoki i tak by się rozjechały.
   */
  await page.locator('[id="iskt-pole-_iskt_trenerzy"]').selectOption({ label: TRENER });

  adresSzkolenia = await opublikujBlokowy(page);

  // Powrót do edycji: przypisanie ma wrócić zaznaczone, a nie puste.
  await page.goto(base + '/wp-admin/edit.php?post_type=iskt_szkolenie');
  await page.locator('#the-list tr', { hasText: SZKOLENIE }).locator('a.row-title').first().click();
  await zamknijPowitanie(page);

  await expect(page.locator('[id="iskt-pole-_iskt_trenerzy"]')).toHaveValues([/\d+/]);
});

test('lista trenerów prowadzi na profil', async ({ page }) => {
  await page.context().clearCookies();
  await page.goto(base + '/trenerzy/');

  await expect(page.locator('h1')).toContainText('Trenerzy');

  const karta = page.locator('.iskt-trener-karta', { hasText: TRENER });
  await expect(karta).toHaveCount(1);
  await expect(karta).toContainText(TRENER_ROLA);
  await expect(karta).toContainText(TRENER_OPIS);

  // Trener bez szkoleń jest pełnoprawną pozycją listy, nie brakiem.
  await expect(page.locator('.iskt-trener-karta', { hasText: TRENER_NOWY })).toHaveCount(1);

  expect(await karta.locator('a').first().getAttribute('href')).toBe(adresTrenera);

  // §11 pkt 10 — żadnego „#” zamiast adresu.
  expect(await page.locator('a[href="#"]').count()).toBe(0);

  for (const szerokosc of [360, 768, 1440]) {
    await page.setViewportSize({ width: szerokosc, height: 900 });
    await page.screenshot({ path: `${shots}/lista-${szerokosc}.png`, fullPage: true });
  }

  /*
   * §7 — kafel musi dać się otworzyć klawiaturą, a fokus musi być widoczny.
   * Kafel jest klikalny w całości, ale przystanek tabulatora ma tylko jeden:
   * odnośnik z nazwiskiem, którego obszar kliknięcia rozciąga się na kartę.
   */
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(base + '/trenerzy/');

  let naKaflu = false;

  for (let krok = 0; krok < 25 && !naKaflu; krok += 1) {
    await page.keyboard.press('Tab');

    naKaflu = await page.evaluate(
      (adres) => document.activeElement instanceof HTMLAnchorElement && document.activeElement.href === adres,
      adresTrenera
    );
  }

  expect(naKaflu).toBe(true);

  const obrys = await page.evaluate(() => {
    const styl = getComputedStyle(document.activeElement);

    return { styl: styl.outlineStyle, szerokosc: parseFloat(styl.outlineWidth) };
  });

  expect(obrys.styl).not.toBe('none');
  expect(obrys.szerokosc).toBeGreaterThan(0);

  await page.screenshot({ path: `${shots}/lista-fokus-1440.png` });
});

test('profil trenera pokazuje komplet danych i znosi odświeżenie', async ({ page }) => {
  await page.goto(adresTrenera);

  // §11 pkt 6 — bezpośrednie wejście pod adres profilu.
  await expect(page.locator('h1')).toContainText(TRENER);

  await expect(page.locator('.iskt-trener-profil__naglowek')).toContainText(TRENER_ROLA);
  await expect(page.locator('.iskt-lead')).toContainText(TRENER_OPIS);
  await expect(page.locator('.iskt-prose')).toContainText(TRENER_BIOGRAFIA);

  await expect(page.locator('body')).toContainText(NAGLOWEK_DOSWIADCZENIE);
  await expect(page.locator('body')).toContainText(DOSWIADCZENIE);
  await expect(page.locator('body')).toContainText(NAGLOWEK_WYKSZTALCENIE);
  await expect(page.locator('body')).toContainText(WYKSZTALCENIE);
  await expect(page.locator('.iskt-trener-profil__specjalizacje')).toContainText(NAGLOWEK_SPECJALIZACJE);
  await expect(page.locator('.iskt-trener-profil__specjalizacje')).toContainText(SPECJALIZACJA);

  await expect(page.locator('.iskt-trener-profil__szkolenia')).toContainText(NAGLOWEK_SZKOLENIA);
  await expect(page.locator('.iskt-trener-profil__szkolenia')).toContainText(SZKOLENIE);

  // §11 pkt 6 — odświeżenie nie gubi widoku.
  await page.reload();
  await expect(page.locator('h1')).toContainText(TRENER);
  await expect(page.locator('.iskt-trener-profil__szkolenia')).toContainText(SZKOLENIE);

  expect(await page.locator('a[href="#"]').count()).toBe(0);

  for (const szerokosc of [360, 768, 1440]) {
    await page.setViewportSize({ width: szerokosc, height: 900 });
    await page.screenshot({ path: `${shots}/profil-${szerokosc}.png`, fullPage: true });
  }
});

test('powiązanie jest spójne w obu widokach i działa „Wstecz”', async ({ page }) => {
  await page.goto(adresSzkolenia);

  // §11 pkt 8, strona pierwsza: trener widoczny przy szkoleniu.
  const prowadzacy = page.locator('.iskt-trenerzy');
  await expect(prowadzacy).toContainText(TRENER);
  await expect(prowadzacy).toContainText(TRENER_ROLA);

  await prowadzacy.locator('a').first().click();
  await page.waitForURL(adresTrenera);

  // §11 pkt 8, strona druga: szkolenie widoczne na profilu trenera.
  const szkolenia = page.locator('.iskt-trener-profil__szkolenia');
  await expect(szkolenia).toContainText(SZKOLENIE);
  expect(await szkolenia.locator('a').first().getAttribute('href')).toBe(adresSzkolenia);

  // §11 pkt 6 — „Wstecz” wraca na stronę szkolenia.
  await page.goBack();
  await expect(page.locator('h1')).toContainText(SZKOLENIE);
});

test('trener bez zdjęcia i bez szkoleń ma kompletny profil', async ({ page }) => {
  await page.goto(adresNowego);

  await expect(page.locator('h1')).toContainText(TRENER_NOWY);

  // Brak zdjęcia: profil pokazuje znak zastępczy, a nie dziurę w układzie (§9).
  await expect(page.locator('.iskt-trener-profil__zdjecie .iskt-avatar')).toHaveCount(1);

  // Brak szkoleń: komunikat z rejestru tekstów, a nie urwana sekcja.
  await expect(page.locator('.iskt-trener-profil__szkolenia')).toContainText(BRAK_SZKOLEN);

  await page.setViewportSize({ width: 1440, height: 900 });
  await page.screenshot({ path: `${shots}/profil-bez-danych-1440.png`, fullPage: true });
});

test('szkolenie wycofane z publikacji znika z profilu trenera', async ({ page }) => {
  await zaloguj(page);
  await page.goto(base + '/wp-admin/edit.php?post_type=iskt_szkolenie');

  /*
   * Wycofanie przez szybką edycję, czyli tak, jak zrobi to właściciel, gdy oferta
   * przestanie być aktualna. §4.4 chroni stronę szkolenia przed usunięciem razem
   * z terminem — ale szkolenie zdjęte z publikacji nie może zostawać na profilu
   * trenera jako odnośnik prowadzący donikąd.
   */
  const wiersz = page.locator('#the-list tr', { hasText: SZKOLENIE }).first();
  const identyfikator = (await wiersz.getAttribute('id')).replace('post-', '');

  await wiersz.hover();
  await wiersz.locator('.editinline').first().click();

  /*
   * Formularz szybkiej edycji celujemy po identyfikatorze wpisu. Lista trzyma
   * jeszcze wzorzec formularza i wiersz edycji zbiorczej — te same nazwy pól
   * w trzech miejscach naraz.
   */
  const formularz = page.locator(`#edit-${identyfikator}`);
  await formularz.locator('select[name="_status"]').selectOption('draft');
  await formularz.locator('.button.save, button.save').first().click();

  await expect(page.locator('#the-list tr', { hasText: SZKOLENIE }).first()).toContainText(/Szkic|Draft/);

  await page.context().clearCookies();
  await page.goto(adresTrenera);

  await expect(page.locator('.iskt-trener-profil__szkolenia')).not.toContainText(SZKOLENIE);
  await expect(page.locator('.iskt-trener-profil__szkolenia')).toContainText(BRAK_SZKOLEN);

  await page.setViewportSize({ width: 1440, height: 900 });
  await page.screenshot({ path: `${shots}/profil-po-wycofaniu-1440.png`, fullPage: true });
});
