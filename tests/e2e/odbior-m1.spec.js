/**
 * ISK-17 M1 — przejście odbiorowe właściciela, wykonane przez panel WordPressa.
 *
 * Kamień milowy M1 brzmi: „właściciel sam dodaje, edytuje i publikuje szkolenie
 * wraz z terminami i trenerem, i widzi je na stronie". Ten test wykonuje dokładnie
 * tę ścieżkę — klikając w panelu, nie przez WP-CLI i nie przez REST. WP-CLI omija
 * skrzynki metadanych i `save_post` z `nonce`, więc udowodniłby coś innego niż
 * kryterium §11 pkt 2.
 *
 * Sprawdzamy przy okazji §4.4: termin zakończony nie może być pokazany jako nadchodzący.
 */
const { test, expect } = require('@playwright/test');

const base = 'http://localhost:8888';
const shots = 'qa-artifacts/isk-17-m1-odbior';

test.describe.configure({ mode: 'serial' });
test.setTimeout(90000);

/** Dane testowe — jawnie demonstracyjne (§9 zabrania udawania zatwierdzonej oferty). */
const TRENER = 'Demo — Anna Kowalska';
const SZKOLENIE = 'Demo — Automatyzacja pracy z AI';
const MODUL_1 = 'Modul pierwszy: podstawy narzedzi generatywnych';
const MODUL_2 = 'Modul drugi: wdrozenie w zespole';
const KORZYSC = 'Uczestnik samodzielnie buduje wlasny przeplyw pracy z AI';
const MIEJSCE_NADCHODZACY = 'Katowice, ul. Demo 1';
const KATEGORIA = 'AI i narzędzia generatywne';

/** Rok w przód i rok wstecz — termin przeszły nie może trafić na stronę. */
const DATA_PRZYSZLA = '2027-06-15';
const DATA_PRZESZLA = '2025-03-10';

let adresSzkolenia = '';
let adresTrenera = '';

async function zaloguj(page) {
  await page.goto(base + '/wp-login.php');

  if (await page.locator('#user_login').isVisible().catch(() => false)) {
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await page.click('#wp-submit');
    await page.waitForURL(/wp-admin/);
  }
}

/**
 * Usuwa opublikowane dane pozostawione przez wcześniejszy/przerwany przebieg.
 *
 * Sprzątamy w panelu, zamiast uzależniać test od WP-CLI. Najpierw terminy, bo
 * wskazują szkolenie; potem szkolenie i trenera. Wpisy w koszu nie trafiają do
 * list wyboru, więc nie powodują niejednoznacznych etykiet przy kolejnym biegu.
 */
async function usunPozostalosci(page, postType, szukanyTytul) {
  const adres = `${base}/wp-admin/edit.php?post_type=${postType}&s=${encodeURIComponent(szukanyTytul)}`;

  while (true) {
    await page.goto(adres);

    const wiersz = page.locator('#the-list tr').filter({
      has: page.locator('a.row-title', { hasText: szukanyTytul }),
    }).first();

    if (!(await wiersz.count())) {
      return;
    }

    await wiersz.locator('a.submitdelete').click({ force: true });
    await page.waitForLoadState('domcontentloaded');
  }
}

test.beforeAll(async ({ browser }) => {
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    await zaloguj(page);
    await usunPozostalosci(page, 'iskt_termin', SZKOLENIE);
    await usunPozostalosci(page, 'iskt_szkolenie', SZKOLENIE);
    await usunPozostalosci(page, 'iskt_trener', TRENER);
  } finally {
    await context.close();
  }
});

/** Modal powitalny edytora blokowego zasłania pole tytułu przy pierwszym wejściu. */
async function zamknijPowitanie(page) {
  const dialog = page.locator('.components-modal__frame');

  if (await dialog.isVisible().catch(() => false)) {
    await dialog.getByRole('button', { name: /close|zamknij/i }).first().click();
    await expect(dialog).toBeHidden();
  }
}

/** Kanwa edytora bywa w ramce (WP 6.6) — tytuł trzeba wpisać tam, gdzie faktycznie jest. */
async function polaTytulu(page) {
  const ramka = page.locator('iframe[name="editor-canvas"]');

  if (await ramka.count()) {
    return page.frameLocator('iframe[name="editor-canvas"]');
  }

  return page;
}

/**
 * Zaznacza pozycję taksonomii w panelu bocznym edytora blokowego.
 *
 * Kategoria i forma decydują o tym, co znajdzie się w katalogu i w sekcji
 * „obszary szkoleń" — bez nich strona główna pokazuje mniej, niż powinna.
 */
async function zaznaczTaksonomie(page, panel, pozycja) {
  const bok = page.locator('.interface-interface-skeleton__sidebar, .editor-sidebar').first();

  if (!(await bok.isVisible().catch(() => false))) {
    await page.getByRole('button', { name: /^(Settings|Ustawienia)$/ }).first().click();
    await bok.waitFor({ state: 'visible' });
  }

  // Panel taksonomii jest na karcie wpisu, nie na karcie bloku.
  const kartaWpisu = bok.getByRole('tab', { name: /^(Post|Wpis|Szkolenie)$/ }).first();

  if (await kartaWpisu.isVisible().catch(() => false)) {
    await kartaWpisu.click();
  }

  const naglowek = bok.getByRole('button', { name: panel }).first();
  await naglowek.waitFor({ state: 'visible' });

  if ((await naglowek.getAttribute('aria-expanded')) !== 'true') {
    await naglowek.click();
  }

  await bok.getByRole('checkbox', { name: pozycja }).first().check();
}

async function wpiszTytul(page, tytul) {
  const korzen = await polaTytulu(page);
  const pole = korzen.locator('.editor-post-title__input, .wp-block-post-title, [aria-label="Add title"]').first();

  await pole.click();
  await pole.fill(tytul);
}

/**
 * Publikuje wpis w edytorze blokowym i zwraca adres, który panel pokazał redaktorowi.
 *
 * Adres bierzemy z panelu, a nie zgadujemy z tytułu: przy powtórzonym tytule
 * WordPress dokleja przyrostek do odnośnika i zgadywanie prowadziłoby na inny wpis.
 */
async function opublikujBlokowy(page) {
  await page.getByRole('button', { name: /^(Publish|Opublikuj)$/ }).first().click();

  const potwierdzenie = page.locator('.editor-post-publish-button');
  await potwierdzenie.waitFor({ state: 'visible' });
  await potwierdzenie.click();

  await expect(page.locator('.post-publish-panel__postpublish-header').first())
    .toContainText(/is now|opublikowan/i, { timeout: 30000 });

  return page.locator('.post-publish-panel__postpublish-post-address input').first().inputValue();
}

test('właściciel dodaje trenera przez panel', async ({ page }) => {
  await zaloguj(page);
  await page.goto(base + '/wp-admin/post-new.php?post_type=iskt_trener');
  await zamknijPowitanie(page);

  await wpiszTytul(page, TRENER);

  // Skrzynka metadanych renderuje się pod edytorem — poza kanwą, w zwykłym DOM strony.
  await page.locator('[id="iskt-pole-_iskt_rola"]').fill('Trenerka AI i automatyzacji');
  await page.locator('[id="iskt-pole-_iskt_doswiadczenie"]').fill('12 lat wdrozen w przemysle\n40 zrealizowanych szkolen');
  await page.locator('[id="iskt-pole-_iskt_specjalizacje"]').fill('Narzedzia generatywne\nAutomatyzacja procesow');

  adresTrenera = await opublikujBlokowy(page);

  await page.goto(base + '/wp-admin/edit.php?post_type=iskt_trener');
  await expect(page.locator('#the-list')).toContainText(TRENER);

  // Ponowne wejście w edycję: pola muszą wrócić zapisane, nie puste.
  await page.locator('#the-list a.row-title').first().click();
  await zamknijPowitanie(page);
  await expect(page.locator('[id="iskt-pole-_iskt_rola"]')).toHaveValue('Trenerka AI i automatyzacji');
});

test('właściciel dodaje szkolenie z programem, korzyściami, ceną i trenerem', async ({ page }) => {
  await zaloguj(page);
  await page.goto(base + '/wp-admin/post-new.php?post_type=iskt_szkolenie');
  await zamknijPowitanie(page);

  await wpiszTytul(page, SZKOLENIE);

  await page.locator('[id="iskt-pole-_iskt_grupa_docelowa"]').fill('Zespoly operacyjne i kadra kierownicza.');
  await page.locator('[id="iskt-pole-_iskt_korzysci"]').fill(KORZYSC + '\nZespol wie, ktore zadania warto zautomatyzowac');

  await page.locator('[name="_iskt_program[0][tytul]"]').fill(MODUL_1);
  await page.locator('[name="_iskt_program[0][opis]"]').fill('Przeglad narzedzi i granice ich stosowania.');

  // Powtarzalny program: drugi moduł powstaje przyciskiem, nie przez edycję kodu (§4.3).
  await page.getByRole('button', { name: /Dodaj moduł/i }).click();
  await page.locator('[name="_iskt_program[1][tytul]"]').fill(MODUL_2);

  await page.locator('[id="iskt-pole-_iskt_poziom"]').selectOption({ index: 1 });
  await page.locator('[id="iskt-pole-_iskt_czas_trwania"]').fill('2 dni (16 godzin)');
  await page.locator('[id="iskt-pole-_iskt_cena"]').fill('2400');
  await page.locator('[id="iskt-pole-_iskt_cena_jednostka"]').selectOption({ index: 1 });
  await page.locator('[id="iskt-pole-_iskt_cena_podatek"]').selectOption({ index: 1 });
  await page.locator('[id="iskt-pole-_iskt_wyroznione"]').check();
  await page.locator('[id="iskt-pole-_iskt_dofinansowanie"]').check();
  await page.locator('[id="iskt-pole-_iskt_dofinansowanie_opis"]').fill('Szkolenie kwalifikuje sie do wsparcia — warunki potwierdzamy indywidualnie.');

  await page.locator('[id="iskt-pole-_iskt_trenerzy"]').selectOption({ label: TRENER });

  // Panel w edytorze podpisany jest nazwą menu taksonomii — „Kategorie”, nie „Kategorie szkoleń”.
  await zaznaczTaksonomie(page, 'Kategorie', KATEGORIA);
  await zaznaczTaksonomie(page, 'Formy realizacji', 'Stacjonarnie');

  adresSzkolenia = await opublikujBlokowy(page);

  await page.goto(base + '/wp-admin/edit.php?post_type=iskt_szkolenie');
  await page.locator('#the-list a.row-title').first().click();
  await zamknijPowitanie(page);

  await expect(page.locator('[name="_iskt_program[0][tytul]"]')).toHaveValue(MODUL_1);
  await expect(page.locator('[name="_iskt_program[1][tytul]"]')).toHaveValue(MODUL_2);
  // Sanityzacja normalizuje cenę do dwóch miejsc po przecinku — stąd „2400.00", nie „2400".
  await expect(page.locator('[id="iskt-pole-_iskt_cena"]')).toHaveValue(/^2400(\.00)?$/);
});

test('właściciel dodaje dwa terminy: nadchodzący i zakończony', async ({ page }) => {
  await zaloguj(page);

  const terminy = [
    { data: DATA_PRZYSZLA, tryb: 'Stacjonarnie', miejsce: MIEJSCE_NADCHODZACY },
    { data: DATA_PRZESZLA, tryb: 'Stacjonarnie', miejsce: 'Gliwice, ul. Demo 2' },
  ];

  for (const termin of terminy) {
    await page.goto(base + '/wp-admin/post-new.php?post_type=iskt_termin');

    // Typ `iskt_termin` jest poza REST, więc panel podaje klasyczny edytor.
    await page.locator('[id="iskt-pole-_iskt_termin_szkolenie"]').selectOption({ label: SZKOLENIE });
    await page.locator('[id="iskt-pole-_iskt_data_start"]').fill(termin.data);
    await page.locator('[id="iskt-pole-_iskt_tryb"]').selectOption({ label: termin.tryb });
    await page.locator('[id="iskt-pole-_iskt_lokalizacja"]').fill(termin.miejsce);
    await page.locator('[id="iskt-pole-_iskt_status_zgloszen"]').selectOption({ index: 1 });

    await page.locator('#publish').click();
    await expect(page.locator('#message')).toContainText(/published|opublikowan/i);
  }

  await page.goto(base + '/wp-admin/edit.php?post_type=iskt_termin');

  /*
   * Wtyczka nadaje terminowi tytuł sama — „szkolenie + data”. Redaktor nie musi
   * wymyślać nazwy dla czegoś, co jest datą, a lista w panelu pozostaje czytelna.
   * Formatu daty nie zaszywamy w asercji: zależy od języka instalacji.
   */
  await expect(page.locator('#the-list')).toContainText('2027');
  await expect(page.locator('#the-list')).toContainText(/2025 \(zakończony\)/);
  await expect(page.locator('#the-list')).toContainText(SZKOLENIE);
});

test('opublikowane szkolenie widać na stronie, bez terminu zakończonego', async ({ page }) => {
  await page.goto(base + '/wp-admin/edit.php?post_type=iskt_szkolenie');
  // Wejście publiczne — bez zalogowania, tak jak zobaczy je odwiedzający.
  await page.context().clearCookies();

  await page.goto(adresSzkolenia);
  expect(page.url()).toContain('/szkolenia/');

  await expect(page.locator('h1')).toContainText('Automatyzacja pracy z AI');
  await expect(page.locator('.iskt-breadcrumb')).toContainText(KATEGORIA);
  await expect(page.locator('.iskt-szkolenie__plakietki')).toContainText(KATEGORIA);
  await expect(page.locator('.iskt-facts')).toContainText('Stacjonarnie');
  await expect(page.locator('body')).toContainText(MODUL_1);
  await expect(page.locator('body')).toContainText(MODUL_2);
  await expect(page.locator('body')).toContainText(KORZYSC);
  await expect(page.locator('body')).toContainText('2 dni (16 godzin)');
  // Separator tysięcy zależy od języka instalacji — sprawdzamy kwotę, nie formatowanie.
  await expect(page.locator('.iskt-price__value')).toContainText(/2\s*400 zł/);
  await expect(page.locator('.iskt-price__note')).toContainText(/netto|brutto/);
  await expect(page.locator('.iskt-trenerzy')).toContainText(TRENER);

  // §4.4 — zakończony termin nie jest nadchodzącym.
  await expect(page.locator('.iskt-terminy')).toContainText(MIEJSCE_NADCHODZACY);
  await expect(page.locator('.iskt-terminy')).not.toContainText('Gliwice');
  await expect(page.locator('.iskt-terminy')).not.toContainText('2025');

  // §11 pkt 10 — żadnego „#” zamiast nawigacji.
  const puste = await page.locator('a[href="#"]').count();
  expect(puste).toBe(0);

  for (const szerokosc of [360, 768, 1440]) {
    await page.setViewportSize({ width: szerokosc, height: 900 });
    await page.screenshot({ path: `${shots}/szkolenie-${szerokosc}.png`, fullPage: true });
  }
});

test('bezpośrednie wejście i odświeżenie działa, profil trenera pokazuje szkolenie', async ({ page }) => {
  await page.goto(adresSzkolenia);
  await page.reload();
  await expect(page.locator('h1')).toContainText('Automatyzacja pracy z AI');

  // Odnośnik ze strony szkolenia musi prowadzić dokładnie do opublikowanego profilu.
  const zeSzkolenia = await page.locator('.iskt-trenerzy a').first().getAttribute('href');
  expect(zeSzkolenia).toBe(adresTrenera);

  await page.goto(adresTrenera);
  await page.reload();

  await expect(page.locator('body')).toContainText(TRENER);

  await page.setViewportSize({ width: 1440, height: 900 });
  await page.screenshot({ path: `${shots}/trener-1440.png`, fullPage: true });

  // Powrót przyciskiem „Wstecz" musi wrócić na stronę szkolenia (§6).
  await page.goBack();
  await expect(page.locator('h1')).toContainText('Automatyzacja pracy z AI');
});

test('wyróżnione szkolenie pojawia się na stronie głównej', async ({ page }) => {
  await page.goto(base + '/');
  await expect(page.locator('.iskt-wyroznione')).toContainText('Automatyzacja pracy z AI');

  // Sekcja „obszary szkoleń" (§4.1) buduje się z kategorii, które mają opublikowane szkolenia.
  await expect(page.locator('.wp-block-iskt-obszary-szkolen')).toContainText(KATEGORIA);

  await page.setViewportSize({ width: 1440, height: 900 });
  await page.screenshot({ path: `${shots}/strona-glowna-z-wyroznionym-1440.png`, fullPage: true });
});
