# Khatt consumer UI redesign: "Gallery Wall"

- **Status:** draft, awaiting review
- **Date:** 2026-10-05
- **Inputs:** `UI-UPGRADE-PROMPT.md`, the audit `DESIGN-IS-2026-10-05/` (14/30 → REDESIGN), and the chosen direction mock `docs/design/directions/a-gallery-wall.html` plus the script ribbon from `c-qalam-atelier.html`.

## 1. Intent

A buyer should feel they have walked into a quiet calligraphy gallery. The artwork is the loudest thing on every screen, and the UI recedes. Success means:

- **Short path to the action.** A buyer can go feed or search → piece → *Add to cart* or *Request similar* in three taps or fewer, on a 375 px phone and on a 1440 px desktop.
- **Honest controls.** Every visible control does what it says, with no dead chips and no placeholders.
- **Higher audit score.** A re-run of the Rams audit scores 20/30 or more, with no principle below 2 on #2, #4 and #6.

## 2. Non-negotiables (from the brief)

- Views, CSS and the shell only. The only backend change is the read-only data for the home page in §5.3.
- RTL-first with logical properties only. All copy goes through `__()` with ar/en parity, which `tests` enforce.
- Every edited file is UTF-8 without BOM.
- Light and dark are both designed. With no `theme` cookie, follow `prefers-color-scheme`.
- No new JS dependency. Use Alpine (bundled with Livewire) or at most a few lines of vanilla JS.
- Animate only `transform` and `opacity`, and respect `prefers-reduced-motion`.
- WCAG AA contrast, visible focus, and keep the skip-link.
- Filament `/admin` and `/studio` are out of scope.
- Every phase ends with `php artisan test` passing and `npm run build` succeeding.

## 3. Visual system

### 3.1 Tokens (`resources/css/app.css` `:root`, evolved in place)

The existing token **names** are kept (`--bg`, `--surface`, `--ink`, `--primary`, …), so all the pages that are not yet redesigned keep working. Only the values change, and a few tokens are added.

| Token | Light | Dark | Role |
|---|---|---|---|
| `--bg` (wall) | `#e9e8e4` | `#1b1a18` | Page background: a cool stone wall, not cream |
| `--surface` (mat) | `#fbfaf7` | `#262420` | Frames, panels, inputs |
| `--surface-2` | `#dedcd6` | `#302d28` | Hover, quiet fills |
| `--ink` | `#1a1816` | `#efeae0` | Primary text |
| `--ink-soft` | `#5b5650` | `#b3ab9e` | Secondary text |
| `--ink-faint` | `#7a746c` | `#8f877a` | Meta text (AA on `--surface`) |
| `--line` | `#cfccc5` | `#3a3631` | Rules, borders |
| `--primary` | `#0f6b5e` | `#4fbfaa` | Primary actions only |
| `--gold` | `#8f6a24` | `#d6b06a` | "Sold" and verified marks only. Text use must be AA, which replaces the old 2.70:1 seal |
| `--danger` | `#b23b2f` | `#e0796e` | Destructive actions only |
| **new** `--shadow-hang` | `0 1px 0 var(--line), 0 22px 40px -28px rgb(26 24 22 / .45)` | the same with alpha `.7` | A framed work "hanging" on the wall |

**Fixes carried by tokens:**
- `.btn-primary` uses `--primary`, not `--danger` (audit E6).
- `.scr-seal` and `.badge-gold` reach AA (audit E9).

### 3.2 Type

There are two families plus the per-script art fonts, which stay unchanged:

- **Display:** Amiri 700, used for page titles, piece titles and the logo. In `en`, display text uses Amiri for the headline only, and the UI is Manrope.
- **UI and body:** IBM Plex Sans Arabic (ar) and Manrope (en).
- **Art:** `config('catalog.script_fonts')`, unchanged.

The scale is fluid and limited to six steps, down from the twelve sizes found in the audit:

| Step | Size |
|---|---|
| `--t-xs` | 12px |
| `--t-sm` | 13.5px |
| `--t-base` | 15px |
| `--t-lg` | `clamp(18px, 1.6vw, 22px)` |
| `--t-xl` | `clamp(28px, 3.4vw, 46px)` |
| `--t-hero` | `clamp(40px, 6vw, 84px)` |

Arabic body line-height is 1.65 and Latin is 1.55. There are no all-caps labels and no eyebrows (the `.eyebrow` class is removed from the home page).

### 3.3 Signature elements

The boldness is spent in these two places only:

1. **The frame and wall label.** Every artwork sits in a `--surface` mat with `--shadow-hang`. Under it is a museum label, separated by a 2 px `--ink` rule on the inline-start edge. The label has three lines: the title (`--t-sm`, 600), the artist and script (`--ink-faint`), and the price, or "Available to commission" in `--primary`, or "Sold" in `--gold`.
2. **The script ribbon.** This is a horizontal row of tiles. Each tile shows the script's own name *written in that script's font* (from `catalog.script_fonts`) and links to `explore?script={id}`. The first tile is "All". On the home page it replaces the dead chips (audit E5).

Ornament is limited to a single thin double rule (`border-top: 6px double var(--line)`) as a section divider. There are no geometric patterns in this direction.

### 3.4 Motion

- **Frame hover:** `translateY(-3px)` with a deeper shadow, 250 ms `cubic-bezier(.2,.7,.3,1)`.
- **Image load:** fades in with opacity over 200 ms.
- **Nothing else** animates. With reduced motion, all transitions are off.

## 4. Shell (`layouts/app.blade.php`)

### Desktop (≥ 860 px)

The 78 px side rail is replaced by a single top bar with no rail:

```
[خَطّ]  Gallery  Explore  |  ________search________  | ع/EN  ☾  🔔  🛒  (avatar ▾)
```

- **Primary nav:** Home ("Gallery") and Explore. These are the only public index routes that exist today. No new pages are added in this redesign.
- **Search:** an underline-style field, with the form action `explore` as today.
- **Avatar menu:** a native `<details>` element with no JS. It holds Profile, Boards, Orders, Commissions, Notifications and Settings. Sellers also get Create piece and Seller orders, and admins get Admin. The role checks stay `@role`/`@auth`, exactly as today. Guests see a "Sign in" button instead.
- **Cart:** an icon with a count, for authenticated users only.

### Mobile (< 860 px)

- **Top bar:** the logo, a search icon (which links to `explore`), and the avatar or "Sign in".
- **Bottom tab bar:** fixed, with safe-area padding. The tabs are Home, Explore, Boards, Cart and Account, and the auth-only tabs are hidden for guests.
- **Language and theme:** move into the avatar menu. This fixes the overflow from audit E7.

### Theme

The inline script runs in `<head>`, so there is no flash of the wrong theme:
- If there is no cookie, the theme follows `matchMedia('(prefers-color-scheme: dark)')`.
- The toggle still writes the cookie.

## 5. Pages

The work is grouped into phases. Each phase replaces the old markup in place, with no feature flags.

### 5.1 Piece card (`partials/pin-card.blade.php` and `livewire/feed.blade.php`)

Both files render cards today. The redesign unifies them by having `feed.blade.php` include the partial (the partial takes a pin array), so there is one card. The card structure is:

- The frame containing either the image or the typeset art.
- The wall label (§3.3).
- No duplicated title over typeset art (audit E10).

**Broken image:** both the `<img>` and the typeset art are rendered, with the art hidden. `onerror="this.hidden=true;this.nextElementSibling.hidden=false"` swaps to the art. This fixes audit E3 for any future URL problem.

**Save and like:** they appear on hover or focus inside the frame corner, and are always visible on touch devices (`@media (hover:none)`).

### 5.2 Feed grid

- `.pin-grid` becomes CSS columns: `columns: 4 260px` with column gap `clamp(24px,3vw,44px)`, and 2 columns under 860 px.
- Cards use `break-inside: avoid`. The typeset art gets a varied aspect ratio from the pin id (3/4, 1/1, 4/3) for a true masonry rhythm.
- **Known trade-off:** columns flow top-to-bottom, so "Load more" items appear at the bottom of each column rather than strictly in reading order. This is accepted for a discovery feed.
- **Loading:** the existing `wire:loading` shows skeleton frames (`--surface-2` blocks with a 1.2 s opacity pulse, disabled under reduced motion) instead of "…".

### 5.3 Home (`pages/home.blade.php`)

The layout, top to bottom:

1. **Hero:** two columns on desktop and stacked on mobile.
   - **Start side:** the `--t-hero` headline uses the existing `shell.home_title` key, with `shell.home_sub` below it.
   - **End side:** one featured piece, framed, with its wall label.
   - **Honest copy:** there is no "exhibition of the week" claim, because no curation feature exists.
2. **Script ribbon** (§3.3).
3. **Feed** (Livewire, unchanged logic).

**Required data (the only backend change).** `HomeController::index` passes:
- `$featured`: the first item of the existing `FeedQuery::firstPage()`, or `null`. When it is `null`, the hero renders without the frame.
- `$scripts`: from the existing `TaxonomyRepository::all()['scripts']`.

There are no new queries, models or Actions.

### 5.4 Piece page (`pages/piece.blade.php`)

- **Layout:** the frame is large on the start side, with the info column on the end side. Below 860 px it stacks.
- **Info column:**
  - The title in Amiri `--t-xl`.
  - The artist block: avatar, name, follower count and the existing follow button. A verified mark appears in `--gold` only if the data already exists.
  - The price.
  - **One primary action:** Add to cart, or Request similar commission when the piece is commission-only or sold. The other action is secondary.
  - A `dl` of details: script, medium, size and year, showing only fields that exist.
  - Save to board.
- **Below the fold:** the existing comments, restyled, then "More from this artist", but only if the controller already passes the data. Otherwise it is out of scope.

### 5.5 Remaining pages, in later phases with the same system

- **Explore:** the filter bars become one quiet filter panel: the ribbon, then theme pills, then a "More filters" `<details>` for color, medium, type and price. Active filters show as removable pills, and there is a result count.
- **Profile:** a cover band (or the wall colour when there is no cover), avatar, bio and counts, then tabs for Pieces and Boards. The tabs are links, not JS.
- **Commission workspace:** a horizontal stepper of lifecycle states (Requested → Quoted → Deposit → In progress → Proof → Balance → Shipped → Completed), with the current step highlighted. It becomes vertical on mobile. Proofs are framed, and Approve and Request revision are primary and secondary. A persistent "paid / due" summary is shown. All actions and states come from what the view already receives.
- **Cart, checkout and orders:** spacious summary panels, with order status as the same stepper component.
- **Auth:** a split screen. One side is a framed typeset piece, the other is the form. It stacks on mobile.
- **Boards, notifications, settings and seller orders:** restyled with tokens and frames only, with no structural change.

### 5.6 States checklist (each page in its phase)

| State | Treatment |
|---|---|
| Empty | A plain sentence that says what to do next, plus one button. A small typeset word (for example «فارغ») as the art in a frame |
| Loading | Skeleton frames (feed); `wire:loading.attr="disabled"` on buttons |
| Error | Form errors under the fields, in `--danger`, with direct wording; branded 404/500 pages keep their copy and get the frame treatment |
| Broken image | Swap to typeset art (§5.1) |
| Focus | 2 px `--primary` outline with 3 px offset on every interactive element |
| Disabled | 50% opacity with `cursor:not-allowed`; never the only signal, so the copy also changes |

## 6. Phases

1. **Tokens and shell:** §3 tokens, §4 shell, the theme script, and fixes for `.btn-primary` and the gold contrast.
2. **Card and feed:** §5.1 and §5.2.
3. **Home and piece:** §5.3 and §5.4.
4. **Explore and profile:** §5.5.
5. **Commission workspace.**
6. **Commerce and auth.**
7. **Remaining pages and states sweep, then a re-run of the Rams audit.**

## 7. Verification per phase

- `php artisan test` passes and `npm run build` succeeds.
- Screenshots of every changed page in ar/en, light/dark, at 375 px and 1440 px.
- Run the `baseline-ui`, `fixing-accessibility` and `fixing-motion-performance` skills on the changed views.
- One commit per phase.

## 8. Out of scope

- Filament panels.
- New features: curation, "calligrapher of the week", search suggestions, image zoom and AI.
- Any change to Actions, policies, the state machine or payments.
