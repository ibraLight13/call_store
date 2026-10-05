# Handoff

````
/make-plan Redesign the Khatt (خَطّ) consumer front end, a Laravel 13 + Blade + Livewire 3 + Tailwind 4 Arabic-calligraphy marketplace at C:\laragon\www\call_store (served from http://127.0.0.1:8000). Current design failed audit at 14/30 with critical gaps in principles #2 useful, #4 understandable and #6 honest (each scored 1).

Verdict paragraph:
> REDESIGN. The front end scores 14/30, below the REFINE threshold of 20. Discovery is not usable (dead search and filters), and the shell breaks on desktop and mobile. That means rebuilding the experience from its purpose, while keeping the brand tokens and the per-script typeset idea.

Why redesign and not refine: the total is below 20, and the primary discovery path (search, explore, filters) is not functional, so polishing the current structure would not fix the task.

Preserve from current design:
- The color and radius token layer with light and dark values: resources/css/app.css lines 8-34 (warm paper, teal --primary #0f7b6c, gold --gold #b8893a).
- The RTL-first logical-property approach and the ar/en locale switch: resources/views/layouts/app.blade.php.
- Per-script typeset fallback art: resources/views/partials/pin-card.blade.php lines 4-7, using config('catalog.script_fonts').
- The brand mark and the Amiri display wordmark: layouts/app.blade.php, .brand.
- The skip-link, :focus-visible ring and prefers-reduced-motion handling.

Discard:
- The nested grid. pages/home.blade.php:28 wraps livewire/feed.blade.php:1, and both are .pin-grid, which produces a single-column feed (#3).
- The 78px side rail on mobile and a topbar that overflows at 375px (E7, #5).
- Decorative filter chips with no behaviour. pages/home.blade.php:22 (#6).
- The duplicated card title under the typeset art. livewire/feed.blade.php, .pcard-title (#10).
- Red --danger as the primary button colour. app.css:215 (#4).

Top moves from the audit:
1. #3/#8 Foundation: restore <!DOCTYPE html> (layouts/app.blade.php:1, currently "!DOCTYPE html>", which causes quirks mode); remove the nested .pin-grid; make storage image URLs work when APP_URL=http://call_store.test (.env:5) and the site is served from 127.0.0.1:8000; on image error fall back to typeset art.
2. #2/#6 Discovery: delete the duplicate placeholder route at routes/web.php:45 so ExploreController serves /explore and search; make the home chips real filter links.
3. #5 Mobile-first shell: a bottom tab bar under 760px with the same role-aware items, a compact topbar, and controls inside a menu.
4. #4 Clarity: primary buttons in teal, --danger only for destructive actions; fix gold seal and badge contrast (2.70:1 → AA).
5. #1/#10 Gallery direction: the artwork is the card; editorial hero strip; masonry feed; per the brief "a gallery, not a shop" in UI-UPGRADE-PROMPT.md.

Redesign principles in priority order:
1. #2 Useful: a buyer can search or filter, open a piece and reach Buy or Request commission in three taps or fewer, on mobile and desktop.
2. #4 Understandable: every control does what it looks like; one primary action per screen, in the brand colour.
3. #3 Aesthetic: one fluid type scale (6 sizes or fewer), one spacing scale, artwork as the figure, and both themes designed (honour prefers-color-scheme when no cookie is set).

Hard constraints: views and CSS only, with no business-logic changes (except the route fix and the image URL fix); all copy through __() with ar/en parity (a test enforces it); files UTF-8 without BOM; no new JS framework (use Alpine via Livewire or a few lines of vanilla JS); Filament /admin and /studio out of scope; php artisan test and npm run build must pass.

Deliverables for the plan:
- New information architecture and shell (desktop rail and topbar, mobile bottom bar)
- Two to three visual direction mocks of the home feed and piece page, for the user to choose before building
- New primary flow: feed → piece → buy/commission, low-fi and labelled, compared with the current flow
- A states checklist per page: empty, loading (skeleton), error (including broken image), success, focus, disabled
- Phased rollout: foundation fixes → tokens and shell → card and feed → piece → profile and explore → commission workspace → commerce and auth → states
- Cutover: each phase replaces the old views in place; no flags or parallel designs

Anti-patterns to guard against:
- Porting the old structure under new styling
- Keeping both designs behind a flag
- Following a trend (glassmorphism, purple gradients) instead of the principles above
- Dropping the Preserve list
````
