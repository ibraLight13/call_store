# Khatt UI Upgrade — Prompt for Claude Code

> Paste everything below the line into a new Claude Code session opened in `C:\laragon\www\call_store`.
> Before pasting, install the skills listed in "Setup" (one time only).

## Setup (one time)

Run these in an interactive `claude` terminal, then restart the session:

```
/plugin install frontend-design@claude-plugins-official
/plugin install ui-ux-pro-max@ui-ux-pro-max-skill
```

Already installed, and used by the prompt: `baseline-ui`, `fixing-accessibility`, `fixing-motion-performance`, `claude-mem:design-is`, `superpowers:brainstorming`, `superpowers:writing-plans`, plus the Laravel Boost MCP (`.mcp.json`).

---

## THE PROMPT

You are the design lead and senior front-end engineer for **خَطّ (Khatt)**, an Arabic calligraphy marketplace. Artists sell ready-made pieces and take custom commissions. Buyers find work through a Pinterest-style feed, save pieces to boards, follow calligraphers, buy, and run commissions through a shared workspace (quote → deposit → proof → revisions → balance → shipping).

The backend is finished and tested (Phases 1–9). **Your job is the consumer front end only: take it from "clean prototype" to a premium, gallery-grade product.** Do not change business logic.

### Skills to use, in this order

1. `claude-mem:design-is`: audit the current UI against Rams' principles. Start the app (`composer dev` or Laragon at `http://call_store.test`) and screenshot every page listed below, in both `ar` (RTL) and `en`, light and dark.
2. `superpowers:brainstorming`: agree the visual direction with me before any code. Show me 2–3 distinct directions as one static HTML mock of the home feed and one of the piece page. I pick one.
3. `frontend-design`: drives every visual decision once a direction is chosen.
4. `ui-ux-pro-max`: use it to check palette, font pairing, and UX rules. Arabic typography decisions come from this brief, not from its defaults.
5. `superpowers:writing-plans`: write a phased plan covering one page group per phase.
6. `baseline-ui`, `fixing-accessibility`, `fixing-motion-performance`: run all three on each phase before calling it done.
7. Laravel Boost MCP: use it for Livewire 3 / Tailwind 4 / Blade docs and conventions. Don't guess APIs.

### Project facts (read these files first)

- Stack: **Laravel 13, Livewire 3, Tailwind 4 (via `@tailwindcss/vite`), Blade, Filament 4**. No React or Vue. Don't add any.
- Design system: `resources/css/app.css` defines the tokens (`--bg`, `--surface*`, `--ink*`, `--primary` teal, `--gold`, radii, shadows, `--f-ar` IBM Plex Sans Arabic, `--f-lat` Manrope, `--f-disp` Amiri) and a component layer (`.topbar`, `.rail`, `.chip`, `.pcard`, `.btn`, `.badge`…). **Evolve these tokens and components. Don't throw them away or replace them with a parallel system.**
- Shell: `resources/views/layouts/app.blade.php` has a sticky topbar with search, an ar/EN switch, a theme toggle (cookie `theme`, `data-theme` on `<html>`), and a left icon rail. The rail is role-aware (`@role('seller')`, `@role('admin')`, `@auth`).
- Pages: `resources/views/pages/*.blade.php`. These are home, explore, piece, profile, boards and board, cart and checkout, orders and order, commissions, commission (workspace), commission-request, create-piece, seller-orders, notifications, settings-profile, and auth login/register. Error pages are in `errors/`.
- Components: `livewire/feed`, `like-button`, `follow-button`, `save-to-board-button`, `partials/pin-card` (piece card: renders the image derivative, or falls back to typeset text using `config('catalog.script_fonts')` per script: Naskh, Thuluth, Diwani, Kufi, Ruqaa…), and `partials/comment`.
- The original approved prototype is `khatt-calligraphy-prototype (1).html`. Read it for intent. The new design should go beyond it, not copy it.
- Architecture rules are in `calligraphy-marketplace-architecture-and-build-plan.md`. Controllers and Livewire classes stay thin, and all behaviour lives in Actions. **The UI work touches views and CSS only.** If a view needs data it doesn't have, stop and tell me.

### Hard constraints

- **RTL-first.** Arabic is the default locale. Use logical properties only (`inset-inline-*`, `margin-inline-*`, `ps-/pe-`). Never use `left`/`right`. Every screen must be checked in both `dir="rtl"` and `dir="ltr"`.
- **Text is localized.** All strings go through `__()` in `lang/ar` and `lang/en`. A parity test fails if a key is missing in either language. Never hard-code copy.
- **Encoding.** Save every file as UTF-8 **without BOM**. A BOM and double-encoding caused mojibake in this project twice. After editing Arabic files, run the tests.
- **Both themes.** Every new color must be a token with light and dark values. Dark mode is a first-class design, not inverted colors.
- **Performance.** Public pages are cached HTML. Use no new JS framework and keep JS to Alpine (bundled with Livewire) or a few lines of vanilla JS. Images must use `loading="lazy"`, explicit sizes, and the existing derivative URLs. Animate only `transform` and `opacity`, and respect `prefers-reduced-motion`.
- **Accessibility.** WCAG AA contrast in both themes, visible focus, a keyboard path through the feed, modals and the commission workspace, and keep the existing skip-link.
- **Out of scope:** the Filament `/admin` and `/studio` panels. Leave them as they are unless I ask.
- **Done means:** `php artisan test` passes, `npm run build` succeeds, and I've seen before/after screenshots of every changed page in ar/en × light/dark at 375px and 1440px.

### The UI I want: "a gallery, not a shop"

**Feeling.** Walking into a quiet calligraphy gallery: warm paper, deep ink, a touch of gold leaf. The art is the loudest thing on every screen. The interface should feel crafted and calm, never like a generic e-commerce template or SaaS dashboard. Think of an editorial magazine layout combined with Pinterest-grade discovery.

**Visual language.**
- Palette: keep the warm paper base and the teal/gold brand, but give it more depth. Add ink-black for display type, a subtle paper grain or texture on large surfaces (CSS only, very light), and gold used sparingly for verified badges, prices and key accents, never as large fills.
- Typography: Arabic is the hero. Set big display headlines in a calligraphic face (Amiri, Aref Ruqaa or Reem Kufi, chosen per context), body in IBM Plex Sans Arabic, and Latin in Manrope. Use a real type scale with fluid `clamp()` sizes and generous line-height for Arabic. Each piece's typeset fallback keeps using its own script font.
- Ornament: Islamic geometric patterns or a thin *tazhib*-style border may appear **once per page at most** (hero, section divider, empty states). Draw them as inline SVG at low contrast. Restraint is the point.
- Depth and motion: soft layered shadows, cards that lift slightly on hover, a smooth image fade-in on load, and page transitions under 250 ms. Nothing should bounce or spin.

**Page by page.**
- **Shell:** a slimmer, more elegant topbar. The search should feel central: an expanding field with recent and suggested scripts. On mobile the rail becomes a bottom tab bar with the same role-aware items, and the topbar gets a compact search button.
- **Home / feed:** a true masonry layout (CSS columns or grid, no library). Add a short editorial hero strip ("Calligrapher of the week", a featured script) above the feed, and sticky script/style filter chips. Use skeleton placeholders while Livewire loads more.
- **Piece card (`pin-card`):** the artwork fills the card with no visible chrome. Save and like appear on hover (and are always visible on touch). The script seal and the price or "commission" tag sit as quiet overlays. The seller's avatar and name appear below.
- **Piece page:** a large gallery-style image with zoom, plus a side panel holding the title in its script font, the artist block with a follow button, price, a clear "Buy" or "Request similar commission" call to action, details (script, medium, size, year), and comments. Finish with "More from this artist" and "Similar pieces" rows.
- **Profile:** a cover band with the artist's signature piece, an avatar, bio, follower counts, and tabs for Pieces, Boards and Reviews. Show the verified-seller mark in gold.
- **Explore:** the filter bar becomes a refined faceted panel (a side sheet on mobile) with active filter pills and a result count.
- **Commission workspace:** the showpiece of the app. Show a **visual timeline/stepper** of the lifecycle states with the current step highlighted. Make proofs large and comparable side by side. Each step gets clear Approve / Request revision actions, and amounts paid vs due are always visible.
- **Cart / checkout / orders:** calm, trustworthy and spacious. Use clear summaries, order status as a progress line, and reassuring empty states.
- **Auth:** a split screen, with a rotating calligraphy piece on one side and the form on the other.
- **Empty, error, loading and 404 states:** each one should be designed, with a small calligraphic illustration and a helpful next action.

**Avoid:** purple gradients, glassmorphism everywhere, generic stock icons as decoration, emoji in the UI, centered-everything hero layouts, more than two accent colors, and anything that looks like a default Tailwind/shadcn template.

### How to work

1. Run the audit and screenshots, then show me the 2–3 direction mocks. **Wait for my pick.**
2. Write the plan and show it to me. **Wait for approval.**
3. Build phase by phase: tokens and shell first, then the card and feed, the piece page, profile and explore, the commission workspace, commerce and auth, and finally the states. After each phase, run tests and build, take screenshots (ar/en × light/dark × mobile/desktop), run the three review skills, and commit with a clear message.
4. Keep diffs small and reuse existing tokens and components before adding new ones.
