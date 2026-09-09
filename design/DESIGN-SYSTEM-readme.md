# ISKT Greenovation — Design System

A brand + UI design system for **ISKT Greenovation Sp. z o.o.**, a Polish technology-and-sustainability consultancy based in Żory. ISKT ("Innowacje w Harmonii z Naturą" — *Innovation in Harmony with Nature*) pairs future technologies with responsible, sustainable development for businesses.

**Services (Kompetencje):** R&D projects (B+R), ESG & Sustainability, AI & Digital Transformation, Startup support, Software Development, Training.

## Sources
- **Live site:** https://iskt.pl/ (WordPress) — single-page marketing site. All copy in this system is lifted verbatim from it.
- **Prototype origin:** the site's Open-Graph assets point to `https://iskt-one-pager-websi-3ivs.bolt.host/` (a Bolt.new build), which is why the visual language reads as modern Tailwind/Bolt (Inter, soft cards, rounded corners).
- **Logo:** supplied by the user (`uploads/logo-1783549450877.png`) and cropped into `assets/`. It is a circular globe split between green **leaves** (nature) and a black **circuit board** (technology), with an "ISKT" wordmark in a leaf→forest green gradient. The brand palette in this system was **sampled directly from that file**.

> The tooling could not pixel-capture the live site or read its exact CSS (cross-origin), so the visual foundations below are derived from the logo + the retrieved page content + the Bolt/Tailwind lineage. See **Caveats** — please confirm exact brand values.

---

## CONTENT FUNDAMENTALS

**Language.** Polish, always. Proper Polish diacritics (ą, ć, ę, ł, ż, ź, ó).

**Voice.** Confident, warm, expert-but-approachable. The brand sells trust and responsibility, not hype.

**Person.** Speaks as **"my"** (we) about the company; addresses the client as **"Twój / Twoją"** (informal singular "your"). E.g. *"Wspieramy organizacje…"*, *"…dopasowanych do potrzeb Twojej firmy."* — warm and direct, never the formal "Państwo".

**Casing.** Section titles use Polish sentence-ish Title Case (*"Nasze Kompetencje"*, *"Rozwój Oprogramowania"*). Eyebrows/labels are UPPERCASE with wide tracking. Body is sentence case.

**Tone examples (verbatim from site):**
- Hero: *"Innowacje w Harmonii z Naturą"* / *"Łączymy technologie przyszłości ze zrównoważonym rozwojem, tworząc rozwiązania dla odpowiedzialnego biznesu."*
- About: *"ISKT Greenovation to zespół ekspertów łączących pasję do innowacji technologicznych."*
- Why: *"Połączenie technologicznej ekspertyzy z odpowiedzialnością za środowisko."*
- CTA verbs: *"Poznaj nasze usługi"*, *"Skontaktuj się"*.

**Numbers as proof.** Short, punchy stats: **100+** Projektów, **50+** Klientów, **15+** Lat, **24/7** Wsparcie.

**Emoji:** none. The brand is professional; iconography carries visual warmth instead.

**Rhythm.** Eyebrow → bold headline → one supporting sentence. Copy is concise; sections rarely exceed a short paragraph.

---

## VISUAL FOUNDATIONS

**Overall vibe.** Clean, airy, optimistic corporate-sustainable. White and pale-green space, one deep-forest brand green doing the heavy lifting, occasional ink-black "tech" bands for contrast. Nothing loud; confidence through restraint.

**Color.** Green is *the* brand signal.
- **Primary** `--green-700 #1E551D` (the wordmark green). Deepest `--green-900 #0A3400`; leaf-lime accent `--green-500 #54841D`; pale leaf `--green-200 #D5EBAC`.
- **Ink** `--ink-900 #0E1511` (faintly green-warm near-black) — the "circuit" side of the mark; used for dark sections, footer, headings.
- **Neutrals** are *warm* greens-of-gray (`--neutral-*`), never cold blue-gray.
- **Status:** success = brand green; info = eco-teal `#1C6E6A`; warning amber; error clay-red. All muted, never neon.
- Avoid: purple/blue SaaS gradients, neon, cold grays.

**Type.** `Inter` for everything (see Caveats — substitute for the site's sans). Extrabold (800) tight-tracked headlines; regular/medium body at generous line-height (1.5–1.65). `JetBrains Mono` for small tech accents (chips, code, the "iskt.pl" footer) — a nod to the circuit motif.

**Spacing & layout.** 4px base grid. Centered `1200px` max-width container, generous section padding (`clamp(4rem, 9vw, 7.5rem)`). Sections alternate background: white → pale-green wash → white → ink → white.

**Backgrounds.** Mostly flat white / `--surface-subtle`. Signature flourish: soft radial **leaf-green glows** (large, blurred, low-opacity radial-gradients) behind the hero and dark bands. Two-stop **leaf→forest gradients** (`--gradient-brand`) on the wordmark, CTAs, and closing slide. No photography in the system (none available); no textures or repeating patterns.

**Corner radii.** Soft. Cards `--radius-lg (16px)`, inputs/chips `--radius-md (12px)`, buttons and pills fully rounded `--radius-pill`.

**Cards.** White surface, `1px` subtle border, soft low shadow. Interactive cards **lift** (`translateY(-4px)`) and deepen their shadow on hover; service tiles additionally flip their icon chip from pale-green to solid green.

**Shadows.** Soft, low, **green-tinted** (`rgba(14,21,17,…)` / brand-green for the elevated CTA) — never harsh pure-black. Scale xs → lg + a `--shadow-brand` glow.

**Borders.** Hairline `1px` in warm neutral for structure; `1.5px` green for emphasis/focus and outline buttons.

**Motion.** Understated. `--dur-fast 130ms` / `--dur-base 220ms` with a gentle `--ease-standard` and a softer `--ease-out` for toggles. Hover = color + shadow + small lift; press = `translateY(1px) scale(0.99)`. Fades and small translations only — no bounces, no infinite loops. Respect `prefers-reduced-motion`.

**Hover / press states.** Buttons darken green on hover (700→800) and lift with the brand shadow; ghost/outline get a pale-green wash. Toggles slide with the softer ease. Focus shows a 3px translucent leaf-green ring (`--focus-ring`).

**Transparency & blur.** The sticky nav is transparent over the hero, then switches to translucent white + `backdrop-filter: blur(12px)` once scrolled. Dark bands use low-opacity white fills (`rgba(255,255,255,0.04)`) for inset cards.

**Imagery vibe.** Warm, natural, green — the leaf side of the logo sets it. When photography is added it should skew warm and organic, not cold/corporate stock.

---

## ICONOGRAPHY

- **Set:** **Lucide** (open-source, the default of Bolt/Tailwind builds — the site's lineage). Thin, rounded, 2px-stroke outline icons that sit comfortably beside Inter.
- **Delivery:** loaded from CDN — `https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js` — then `lucide.createIcons()` swaps `<i data-lucide="name">` for inline SVG. The `Icon` component wraps this. No icon font, no local sprite (the source provided none).
- **Substitution flag:** the live site's exact icon set could not be confirmed from source; **Lucide is a best-match substitution.** Swap freely if ISKT uses a different set.
- **Brand vocabulary:** `leaf`, `sprout`, `recycle` (nature/ESG); `cpu`, `code-xml`, `brain-circuit` (tech/AI); `flask-conical` (B+R), `rocket` (startups), `graduation-cap` (training), `shield-check`, `trending-up`, `globe`; plus `mail`, `phone`, `map-pin`, `arrow-right`, `menu` for UI.
- **Usage:** icons live in rounded chips (`--green-50` bg, `--green-700` glyph) on service tiles; standalone in nav/contact. **No emoji.** No unicode-glyph icons (except the `▾` select chevron and `✓`/`×` control glyphs).
- **Logo:** never redraw it. Use `assets/iskt-logo-transparent.png` (or `-logo.png` on white). The `Logo` component falls back to an "ISKT" wordmark in brand green when no image path is passed.

---

## Components (`components/`)

Import from `window.ISKTGreenovationDesignSystem_1705b2` after loading `_ds_bundle.js`.

**forms/** — `Button`, `IconButton`, `Input`, `Textarea`, `Select`, `Checkbox`, `Radio`, `Switch`
**content/** — `Card`, `Badge`, `Tag`, `StatCard`, `ServiceCard`, `SectionHeading`
**brand/** — `Logo`, `Icon`

Each directory has a `*.card.html` specimen (Design System tab, "Components" group). Every component ships a `.d.ts` (props), a `.prompt.md` (usage), and a `.jsx` implementation styled entirely with the CSS token variables.

**Intentional additions.** `Icon` (a Lucide wrapper) and `Logo` are not "components" defined by the source, but are needed to use the brand's glyph set and mark consistently. `StatCard`, `ServiceCard`, `SectionHeading` codify the three repeating patterns of the source page.

## UI kit (`ui_kits/website/`)
Interactive, high-fidelity recreation of the full ISKT one-pager: `Nav`, `Hero`, `About` (stats), `Services`, `WhyUs` (dark band), `Contact` (working fake form), `Footer`. Open `ui_kits/website/index.html`. See its `README.md`.

## Slides (`slides/`) & Deck template (`templates/deck/`)
- `slides/` — five standalone 1280×720 slide specimens (Title, Services, Stats, Quote, Closing) tagged for the "Slides" group.
- `templates/deck/Deck.dc.html` — a reusable 1920×1080 branded deck (deck-stage shell) consuming projects can start from.

---

## Index / manifest (root)
- `styles.css` — **the** entry point consumers link. `@import` manifest only.
- `tokens/` — `fonts.css`, `colors.css`, `typography.css`, `spacing.css`, `effects.css`, `base.css`.
- `components/` — `forms/`, `content/`, `brand/` (see above).
- `guidelines/` — foundation specimen cards (Colors, Type, Spacing, Brand groups) + `logo.html`.
- `ui_kits/website/` — the marketing one-pager kit.
- `slides/` — deck slide specimens.
- `templates/deck/` — the branded deck template + `deck-stage.js` + `ds-base.js`.
- `assets/` — `iskt-logo.png` (white bg), `iskt-logo-transparent.png`.
- `SKILL.md` — Agent-Skills manifest for downloadable use.
- Generated (do not edit): `_ds_bundle.js`, `_ds_manifest.json`, `_adherence.oxlintrc.json`.

## Caveats & substitutions
> **Decyzja klienta (2026-07):** substytucje poniżej zatwierdzone bez zmian ("decyduj sam"). System jest polskojęzyczny — wersja angielska nieprzewidziana.

1. **Fonts are substituted.** No brand font binaries were provided; **Inter** (site's likely Bolt/Tailwind default) + **JetBrains Mono** are loaded from Google Fonts. Please confirm the real typefaces or provide files.
2. **Icons are substituted.** **Lucide** is a best-match for the site's set (unconfirmed from source).
3. **Colors are sampled from the logo**, not from the site's stylesheet (cross-origin — couldn't be read). They read true to the mark but may differ from the site's exact hex values.
4. **No live-site screenshots** were possible (cross-origin capture blocked), so section layouts are a faithful reconstruction from the retrieved page content, not a pixel trace.
