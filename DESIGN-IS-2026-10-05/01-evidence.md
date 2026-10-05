# Evidence

All measurements come from the live page (browser JS and computed styles) or from source files, as cited.

## E1 — Document renders in quirks mode
- `resources/views/layouts/app.blade.php:1` starts with `!DOCTYPE html>`. The leading `<` is missing (the first bytes are `33 68 79…`, which is `!DOC…`).
- Live: `document.compatMode === "BackCompat"`, and the literal text `!DOCTYPE html>` is visible above the topbar on every page, at both desktop and mobile widths.

## E2 — Feed collapses to a single narrow column
- `pages/home.blade.php:28` wraps `@livewire('feed')` in `<div class="pin-grid">`.
- `livewire/feed.blade.php:1` has a root element that is also `<div class="pin-grid">`.
- As a result, the inner grid occupies one `minmax(250px,1fr)` cell of the outer grid (`app.css:135`).
- At 1440 px, all 12 cards stack in one column of about 250 px, and roughly 80% of the canvas is empty.
- At 375 px, the cards are about 75 px wide, and titles and prices are clipped ("1,900", "Com…").

## E3 — All piece images broken
- 6 of 6 `<img>` elements have `naturalWidth = 0`. Their URLs are `http://call_store.test/storage/pieces/1/1-480.jpg` and similar.
- `.env:5` sets `APP_URL=http://call_store.test`, but the site is served from `127.0.0.1:8000`.
- Broken images show alt text over a beige box. There is no fallback to the typeset `.pcard-art` design.

## E4 — Explore and search are a "coming soon" placeholder
- `routes/web.php:42` defines `/explore` → `ExploreController`.
- `routes/web.php:45` defines it again → `pages.placeholder`. The second definition wins.
- The topbar search form (`layouts/app.blade.php`) submits to `explore`, so every search lands on the placeholder card that reads "قريبًا" ("coming soon").

## E5 — Home filter chips do nothing
- `pages/home.blade.php:22`: the chips are `<button class="chip">` elements with no `wire:click`, link or form. Clicking one has no effect. There are 9 such buttons.

## E6 — Primary button uses the danger color
- `app.css:215`: `.btn-primary{background:var(--danger)}`, which is red `#c0473b`. This is used for "Sign in" (`auth/login.blade.php:26`) and other primary calls to action.
- The brand primary is teal (`--primary:#0f7b6c`), so every main action reads as destructive.

## E7 — Mobile shell overflows
- At 375 px, the topbar search takes the full width, and the language switch, theme toggle and login button are pushed off-screen. There is no horizontal reflow.
- The 78 px side rail stays on mobile and takes about 20% of the width. There is no bottom navigation.

## E8 — Dark mode ignores the OS setting
- The theme comes only from the `theme` cookie (`layouts/app.blade.php:3`, which defaults to `'light'`).
- With `prefers-color-scheme: dark` emulated, the page still renders light.

## E9 — Visual system
- **Type scale:** 12 distinct sizes on home: 10, 10.5, 12, 12.5, 13, 13.5, 14, 14.5, 15, 19, 27 and 30 px. There is no fluid scale.
- **Colors:** 13 distinct rendered text colors, all from tokens (`app.css:8-34`). The token system is coherent.
- **Lowest contrast:** the script seal and gold badge (`.scr-seal`, `.badge-gold`) measure **2.70:1**, which fails AA. The `.name` brand wordmark measured 1.20:1, probably a measurement artifact from the gradient background, so it needs a manual check.
- **States:** focus ring present (`app.css` `:focus-visible`); skip-link present; branded 404 page present; load-more uses a "…" text-only loading state; no skeletons; no broken-image state; the explore empty state is the placeholder.

## E10 — Structure and weight
- **Home:** 55 interactive elements and 3 landmarks (header, nav, main). There is no footer.
- **Network:** 10 requests and 447 KB transferred, mostly 5 Google font families (Amiri, Aref Ruqaa, Reem Kufi, IBM Plex Sans Arabic, Manrope).
- **App JS:** `app.js` is 0 bytes; Livewire is the only JS.
- **Motion:** `prefers-reduced-motion` is respected, and nothing animates while idle.
- **Duplication:** each card renders the piece text twice, once as the typeset art and again as `.pcard-title` directly below it.

## E11 — What is strong
- A coherent token layer with light and dark values (`app.css:8-34`), built RTL-first on logical properties.
- Typeset fallback art that uses the correct calligraphic font per script (`partials/pin-card.blade.php:4-7`), which is a distinctive idea.
- The piece page layout (`pages/piece.blade.php`): large artwork, a spec table, and comments.
- Warm paper palette with restrained teal and gold.
