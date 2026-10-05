# Khatt "Gallery Wall" Redesign: Phases 1–3 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the consumer UI's colour system, app shell, piece card, feed, home page and piece page with the approved "Gallery Wall" design. This plan also fixes the double-encoded English strings found while planning.

**Architecture:**
- All styling lives in `resources/css/app.css`, which evolves in place. Token names are kept, so pages that haven't been redesigned yet keep rendering.
- A single `PinPresenter` turns a `Piece` into the plain array that one Blade card partial renders. Both the Livewire feed and every Blade include use it, so there is exactly one card.
- The only backend change is that `HomeController` passes data that already exists: the first feed item and the script taxonomy.

**Tech Stack:** Laravel 13, Blade, Livewire 3, Tailwind 4 (used only as the `@import` entry; the app uses hand-written CSS), Pest 4.

**Spec:** `docs/superpowers/specs/2026-10-05-khatt-gallery-redesign-design.md`

**Scope:** spec §6 phases 1–3 (tokens and shell, card and feed, home and piece). Phases 4–7 (explore, profile, commission workspace, commerce, auth, states sweep and audit re-run) get their own plan after this one ships, written against the real result.

## Global Constraints

- Views and CSS only. The single allowed backend change is `HomeController::index` passing `$featured` and `$scripts` (spec §5.3).
- RTL-first: only logical properties (`inset-inline-*`, `margin-inline-*`, `padding-inline-*`, `border-inline-*`). Never use `left`/`right`.
- All copy goes through `__()`. Every new key is added to **both** `lang/ar/*.php` and `lang/en/*.php`; `TranslationParityTest` fails otherwise.
- Every edited file is UTF-8 **without BOM**. Edit with the Edit/Write tools; never round-trip files through PowerShell `Set-Content`/`Out-File`.
- Light and dark are both designed. With no `theme` cookie, follow `prefers-color-scheme`.
- No new npm or composer dependency. JS is limited to inline vanilla one-liners.
- Animate only `transform` and `opacity`. The existing global `prefers-reduced-motion` rule must stay.
- WCAG AA contrast, a visible `:focus-visible` ring, and the skip-link stays.
- Filament `/admin` and `/studio` must not be touched.
- Each task ends with `php artisan test --compact` passing. Tasks that change CSS also need `npm run build` to succeed.
- Commit messages end with `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`. On Windows, commit with `git commit -F -` and a heredoc (Bash tool), because PowerShell mangles quotes inside `-m`.

## Review Focus

1. **Empty catalog.** With no approved pieces, the home page must render (no hero frame and the empty-feed message), not crash on a `null` featured piece. This is pinned in Task 5.
2. **Broken or missing image file.** The card and the piece page must fall back to typeset art. This is pinned in Task 3 (card); the piece page uses the same markup.
3. **The viewer is the seller.** On their own piece page they must see no Follow and no "Request similar" button. This is pinned in Task 6.
4. **Guests.** The shell shows "Log in" and no account-only menu items or tabs. "Add to cart" sends a guest to login. This is pinned in Tasks 2 and 6.
5. **English locale.** No mojibake (`â€`) can reach the page, and the separators are locale-correct. This is pinned in Task 1, and the separator by `shell.list_sep` in Task 3.

## Deviations from the spec (decided while planning, flagged for review)

- **Save/like on hover on feed cards (spec §5.1) is deferred.** Today each needs its own Livewire component, so 24+ per feed page, which would undo the feed's flat query-count test. Save and like stay on the piece page. They can be revisited with a lighter mechanism in the phase 4 plan.
- **Language and theme switches live in the account menu on desktop too**, not only on mobile (spec §4). It is one place, with less chrome in the top bar.
- **`--ink-faint` is `#6f6961`, not `#7a746c`.** The spec value fails AA on the `--bg` wall; the new value passes on both `--bg` and `--surface`.
- **Logout becomes reachable.** `#logout-form` exists today but nothing submits it. The account menu gets a "Log out" button.

## File map

| File | Responsibility | Tasks |
|---|---|---|
| `lang/en/{shell,commerce,commissions}.php` | Repair the 27 double-encoded strings | 1 |
| `lang/{ar,en}/shell.php` | New keys: `nav_main`, `search`, `language`, `list_sep`, `request_similar`, `cm` | 2, 3, 6 |
| `resources/css/app.css` | Tokens, shell, card and feed, hero and ribbon, piece page | 2–6 |
| `resources/views/layouts/app.blade.php` | Top bar, account menu, mobile tab bar, theme script | 2 |
| `app/Support/PinPresenter.php` (new) | `Piece` → card array | 3 |
| `resources/views/partials/pin-card.blade.php` | The one gallery card (takes `$pin`) | 3 |
| `resources/views/{pages/board,pages/explore}.blade.php` | Switch includes to `PinPresenter` | 3 |
| `app/Livewire/Feed.php`, `resources/views/livewire/feed.blade.php` | Feed uses the presenter and partial; skeletons; empty state | 4 |
| `app/Http/Controllers/HomeController.php`, `resources/views/pages/home.blade.php`, `resources/views/partials/script-ribbon.blade.php` (new) | Hero and ribbon | 5 |
| `resources/views/pages/piece.blade.php` | Piece page | 6 |
| `tests/Feature/GalleryUiTest.php` (new), `tests/Feature/TranslationParityTest.php` | Tests | 1–6 |

---

### Task 1: Repair double-encoded English strings and guard against recurrence

27 strings in `lang/en/{shell,commerce,commissions}.php` contain UTF-8 that was decoded as Windows-1252 and re-encoded. For example, `…` became `â€¦`. The site currently shows `artistâ€¦` in the English search placeholder.

**Files:**
- Modify: `lang/en/shell.php`, `lang/en/commerce.php`, `lang/en/commissions.php` (repaired by script)
- Test: `tests/Feature/TranslationParityTest.php`

**Interfaces:** none.

- [ ] **Step 1: Write the failing test.** Append to `tests/Feature/TranslationParityTest.php`:

```php
it('has no BOM or double-encoded (mojibake) text in any lang file', function () {
    // "â€" is how ’ “ ” … — look after a UTF-8 → cp1252 → UTF-8 round trip;
    // "Ø" / "Ù" are the lead characters of double-encoded Arabic.
    $markers = ["\u{00E2}\u{20AC}", "\u{00C3}", "\u{00D8}", "\u{00D9}"];

    foreach (File::allFiles(lang_path()) as $file) {
        $raw = file_get_contents($file->getPathname());
        $name = $file->getRelativePathname();

        expect(str_starts_with($raw, "\xEF\xBB\xBF"))->toBeFalse("BOM in {$name}");

        foreach ($markers as $marker) {
            expect(str_contains($raw, $marker))->toBeFalse("Mojibake in {$name}");
        }
    }
});
```

- [ ] **Step 2: Run it to verify it fails.**
  - Run: `php artisan test --compact --filter="double-encoded"`
  - Expected: FAIL with `Mojibake in en/commerce.php`.

- [ ] **Step 3: Repair with a one-off script.** Create `fix-mojibake.php` at the repo root:

```php
<?php
// One-off: reverse a UTF-8 → Windows-1252 → UTF-8 double encoding, line by line.
// Only touches lines containing the "â€" marker; skips any line that would not round-trip cleanly.
foreach (glob(__DIR__.'/lang/*/*.php') as $file) {
    $lines = file($file);
    $changed = false;

    foreach ($lines as $i => $line) {
        if (! str_contains($line, "\u{00E2}\u{20AC}")) {
            continue;
        }

        $fixed = mb_convert_encoding($line, 'Windows-1252', 'UTF-8');

        if (! mb_check_encoding($fixed, 'UTF-8') || substr_count($fixed, '?') !== substr_count($line, '?')) {
            fwrite(STDERR, "SKIPPED {$file}:".($i + 1)."\n");
            continue;
        }

        $lines[$i] = $fixed;
        $changed = true;
    }

    if ($changed) {
        file_put_contents($file, implode('', $lines));
        echo "fixed {$file}\n";
    }
}
```

  Run: `php fix-mojibake.php`. Expected output: three `fixed …` lines and no `SKIPPED` lines. If a line is skipped, repair it by hand with the Edit tool, using the intended character (`—` `…` `’` `“` `”`).

  Then delete the script: `rm fix-mojibake.php`.

- [ ] **Step 4: Run the tests to verify they pass.**
  - Run: `php artisan test --compact --filter=Translation`
  - Expected: PASS, 2 tests.
  - Spot-check: `grep -n "search_ph" lang/en/shell.php` shows `artist…'`.

- [ ] **Step 5: Commit.**

```bash
git add lang/en tests/Feature/TranslationParityTest.php
git commit -F - <<'EOF'
fix(i18n): repair 27 double-encoded English strings and guard lang files against BOM/mojibake

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
EOF
```

---

### Task 2: Tokens and app shell (spec §3.1, §3.2, §4)

**Files:**
- Modify: `resources/css/app.css`. Replace lines 8–34 (`:root` and `[data-theme="dark"]`) and the `Topbar` and `Layout` sections (from `/* ---------- Topbar` up to `/* ---------- Chips`). Also replace the `.btn-primary` lines and the final `@media(max-width:680px)` block.
- Modify: `resources/views/layouts/app.blade.php` (full rewrite)
- Modify: `lang/ar/shell.php`, `lang/en/shell.php`
- Test: `tests/Feature/GalleryUiTest.php` (create)

**Interfaces:**
- Produces these CSS tokens, used by every later task:
  - Colours: `--bg --surface --surface-2 --surface-3 --ink --ink-soft --ink-faint --line --line-strong --primary --primary-ink --primary-wash --on-primary --gold --gold2 --gold-wash --danger`
  - Shadows: `--shadow-1 --shadow-2 --shadow-hang`
  - Type scale: `--t-xs --t-sm --t-base --t-lg --t-xl --t-hero`
  - Other: `--f-ar --f-lat --f-disp --ease --top-h --tab-h`
- Produces these CSS classes: `.page .dot .btn-primary`
- Produces these lang keys: `shell.nav_main`, `shell.search`, `shell.language`

- [ ] **Step 1: Write the failing tests.** Create `tests/Feature/GalleryUiTest.php`:

```php
<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

it('lets the OS pick the theme until the user chooses one', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<html lang="ar" dir="rtl">', false)          // no server-forced data-theme
        ->assertSee('document.documentElement.dataset.theme', false);
});

it('keeps the danger color off primary buttons', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('.btn-primary{background:var(--primary)')
        ->not->toContain('.btn-primary{background:var(--danger)');
});

it('renders the top-bar shell with a mobile tab bar and no side rail', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('class="topnav"', false)
        ->assertSee('class="tabbar"', false)
        ->assertDontSee('class="rail"', false)
        ->assertSee(route('login'), false);
});

it('shows seller tools only to sellers, and logout to everyone signed in', function () {
    $this->seed(RoleSeeder::class);

    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $this->actingAs($seller)->get('/')
        ->assertSee(route('create'), false)
        ->assertSee('form="logout-form"', false);

    $this->actingAs($buyer)->get('/')
        ->assertDontSee(route('create'), false)
        ->assertSee('form="logout-form"', false);
});
```

- [ ] **Step 2: Run them to verify they fail.**
  - Run: `php artisan test --compact tests/Feature/GalleryUiTest.php`
  - Expected: 4 FAIL (`data-theme` is present, `.btn-primary` uses `--danger`, there is no `topnav`, and there is no `logout-form` button).

- [ ] **Step 3: Add lang keys.** In `lang/ar/shell.php`, add after `'nav_profile' => …,`:

```php
    'nav_main' => 'التنقل الرئيسي',
    'search' => 'بحث',
    'language' => 'اللغة',
```

  In `lang/en/shell.php`, add after `'nav_profile' => 'Profile',`:

```php
    'nav_main' => 'Main navigation',
    'search' => 'Search',
    'language' => 'Language',
```

- [ ] **Step 4: Replace the tokens.** In `resources/css/app.css`, replace the whole `:root{…}` block **and** the `[data-theme="dark"]{…}` block (lines 8–34) with:

```css
/* One declaration per token; light-dark() picks the value from color-scheme,
   which follows the OS unless the user's cookie pins data-theme.
   ponytail: light-dark() needs Chrome 123 / Safari 17.5 / Firefox 120 — older browsers get unstyled colors. */
:root{
    color-scheme:light dark;
    --bg:light-dark(#e9e8e4,#1b1a18);
    --surface:light-dark(#fbfaf7,#262420);
    --surface-2:light-dark(#dedcd6,#302d28);
    --surface-3:light-dark(#d3d0c9,#3a3631);
    --ink:light-dark(#1a1816,#efeae0);
    --ink-soft:light-dark(#5b5650,#b3ab9e);
    --ink-faint:light-dark(#6f6961,#9a9286);
    --line:light-dark(#cfccc5,#3a3631);
    --line-strong:light-dark(#b9b5ad,#4a453e);
    --primary:light-dark(#0f6b5e,#4fbfaa);
    --primary-ink:light-dark(#0b5148,#7fd3c2);
    --primary-wash:light-dark(#dcebe7,#1b3330);
    --on-primary:light-dark(#ffffff,#0f1f1c);
    --gold:light-dark(#7d5c1e,#d6b06a);
    --gold2:light-dark(#b8893a,#a88545);
    --gold-wash:light-dark(#f1e7d2,#2e2616);
    --danger:light-dark(#b23b2f,#e0796e);
    --teal:var(--primary);
    --shadow-1:0 1px 2px light-dark(rgb(20 18 15 / .06),rgb(0 0 0 / .35));
    --shadow-2:0 2px 6px light-dark(rgb(20 18 15 / .06),rgb(0 0 0 / .35)),0 12px 30px light-dark(rgb(20 18 15 / .08),rgb(0 0 0 / .45));
    --shadow-hang:0 1px 0 var(--line),0 22px 40px -28px light-dark(rgb(26 24 22 / .45),rgb(0 0 0 / .7));
    --radius:16px; --radius-sm:11px; --radius-lg:22px;
    --top-h:64px; --tab-h:64px;
    --t-xs:12px; --t-sm:13.5px; --t-base:15px;
    --t-lg:clamp(18px,1.6vw,22px); --t-xl:clamp(28px,3.4vw,46px); --t-hero:clamp(40px,6vw,84px);
    --f-ar:'IBM Plex Sans Arabic',system-ui,sans-serif;
    --f-lat:'Manrope',system-ui,sans-serif;
    --f-disp:'Amiri',serif;
    --ease:cubic-bezier(.2,.7,.3,1);
}
:root[data-theme="light"]{color-scheme:light}
:root[data-theme="dark"]{color-scheme:dark}
```

  Also change the `body` rule's `line-height:1.55` to `line-height:1.65`. Keep `html[dir="ltr"] body{font-family:var(--f-lat)}` and add a line after it:

```css
html[dir="ltr"] body{line-height:1.55}
```

- [ ] **Step 5: Replace the shell CSS.** Delete everything from `/* ---------- Topbar ---------- */` up to (not including) `/* ---------- Chips ---------- */`. That removes `.topbar .brand .search .controls .seg .iconbtn .avatar .shell .rail main .page .h1 .sub .eyebrow`; the replacement below re-adds `.page .h1 .sub .eyebrow`, because other pages still use them. Keep `.auth-mark` (login and register use it); it lives in the Auth section, not here. In its place, put:

```css
/* ---------- Shell: top bar, account menu, mobile tab bar ---------- */
.topbar{
    position:sticky;top:0;z-index:60;height:var(--top-h);
    display:flex;align-items:center;gap:clamp(12px,2vw,28px);padding:0 clamp(16px,4vw,56px);
    background:color-mix(in srgb,var(--bg) 90%,transparent);backdrop-filter:blur(10px);
    border-bottom:1px solid var(--line);
}
.brand{font-family:var(--f-disp);font-size:32px;font-weight:700;line-height:1;flex-shrink:0}
.topnav{display:flex;gap:22px;font-size:var(--t-sm)}
.topnav a{color:var(--ink-soft);padding:6px 0;border-bottom:2px solid transparent}
.topnav a:hover{color:var(--ink)}
.topnav a[aria-current]{color:var(--ink);font-weight:600;border-color:var(--ink)}
.search{
    flex:1;max-width:480px;margin-inline-start:auto;display:flex;align-items:center;gap:8px;
    border-bottom:1.5px solid var(--line-strong);padding:6px 2px;color:var(--ink-faint);
}
.search:focus-within{border-color:var(--ink)}
.search svg{width:18px;height:18px;flex-shrink:0}
.search input{flex:1;min-width:0;border:0;background:none;font:inherit;color:var(--ink);outline:none}
.search input::placeholder{color:var(--ink-faint)}
.controls{display:flex;align-items:center;gap:6px;flex-shrink:0}
.iconbtn{position:relative;width:40px;height:40px;border-radius:50%;display:grid;place-items:center;color:var(--ink-soft)}
.iconbtn:hover{color:var(--ink);background:var(--surface-2)}
.iconbtn svg{width:20px;height:20px}
.search-mobile{display:none}
.count{
    position:absolute;top:2px;inset-inline-end:2px;min-width:17px;height:17px;padding:0 4px;border-radius:9px;
    background:var(--danger);color:#fff;font-size:10px;font-weight:700;display:grid;place-items:center;
}
.dot,.avatar{
    width:40px;height:40px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;
    background:var(--ink);color:var(--surface);font-weight:600;
}
.menu{position:relative}
.menu summary{list-style:none;cursor:pointer}
.menu summary::-webkit-details-marker{display:none}
.menu-pop{
    position:absolute;top:calc(100% + 10px);inset-inline-end:0;z-index:70;min-width:230px;display:grid;padding:6px;
    background:var(--surface);border:1px solid var(--line);border-radius:var(--radius-sm);box-shadow:var(--shadow-2);
}
.menu-pop a,.menu-pop button{display:block;width:100%;text-align:start;padding:9px 12px;border-radius:8px;font-size:var(--t-sm);color:var(--ink)}
.menu-pop a:hover,.menu-pop button:hover{background:var(--surface-2)}
.menu-sep{height:1px;background:var(--line);margin:6px 4px}
.menu-lang{display:flex;gap:4px}
.menu-lang a{text-align:center}
.menu-lang a[aria-current]{background:var(--surface-2);font-weight:600}
.tabbar{display:none}

main{display:block;min-height:calc(100vh - var(--top-h))}
.page{padding:clamp(20px,3vw,40px) clamp(16px,4vw,56px) 80px;max-width:1600px;margin:0 auto}
.h1{font-family:var(--f-disp);font-size:var(--t-xl);font-weight:700;line-height:1.3;margin:0 0 6px}
.sub{color:var(--ink-soft);margin:0 0 20px;font-size:var(--t-sm)}
/* Still used by board, boards, commission-request, settings-profile until their phase; spec §3.2: no all-caps. */
.eyebrow{font-size:var(--t-sm);font-weight:600;color:var(--primary);margin-bottom:6px}

@media(max-width:859px){
    .topnav,.search,.hide-mobile{display:none}
    .search-mobile{display:grid}
    .controls{margin-inline-start:auto}
    .tabbar{
        display:flex;position:fixed;inset-inline:0;bottom:0;z-index:60;justify-content:space-around;align-items:center;
        height:calc(var(--tab-h) + env(safe-area-inset-bottom));padding-bottom:env(safe-area-inset-bottom);
        background:var(--surface);border-top:1px solid var(--line);
    }
    .tabbar a{display:grid;justify-items:center;gap:2px;min-width:56px;font-size:11px;color:var(--ink-soft)}
    .tabbar a[aria-current]{color:var(--ink);font-weight:600}
    .tabbar svg{width:22px;height:22px}
    .page{padding-bottom:calc(var(--tab-h) + 32px)}
}
```

  Replace the two `.btn-primary` lines with:

```css
.btn-primary{background:var(--primary);color:var(--on-primary)}
.btn-primary:hover{background:var(--primary-ink)}
```

  Delete the final block `@media(max-width:680px){ .brand .tag … :root{--rail-w:60px} }`, because it only styled the removed rail and tagline.

  Also delete the duplicated second `.canvas{…}` / `.canvas .art` / `.canvas .paper-edge` trio (they are identical to the first; keep the first).

- [ ] **Step 6: Rewrite the layout.** Replace `resources/views/layouts/app.blade.php` entirely with:

```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('shell.nav_home')) — خَطّ</title>
    <meta name="description" content="@yield('meta_description', __('shell.tagline'))">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:site_name" content="خَطّ">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', __('shell.nav_home')) — خَطّ">
    <meta property="og:description" content="@yield('meta_description', __('shell.tagline'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary">

    {{-- Before first paint: a saved choice pins the theme; otherwise color-scheme follows the OS. --}}
    <script>(function(){var m=document.cookie.match(/(?:^|;\s*)theme=(light|dark)/);if(m)document.documentElement.dataset.theme=m[1];})();</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Aref+Ruqaa:wght@400;700&family=Reem+Kufi:wght@400;500;700&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<a class="skip-link" href="#main-content">{{ __('shell.skip_to_content') }}</a>

<header class="topbar">
    <a class="brand" href="{{ route('home') }}">خَطّ</a>

    <nav class="topnav" aria-label="{{ __('shell.nav_main') }}">
        <a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>{{ __('shell.nav_home') }}</a>
        <a href="{{ route('explore') }}" @if (request()->routeIs('explore')) aria-current="page" @endif>{{ __('shell.nav_explore') }}</a>
    </nav>

    <form class="search" role="search" action="{{ route('explore') }}" method="get">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-3.5-3.5"/></svg>
        <input name="q" value="{{ request('q') }}" placeholder="{{ __('shell.search_ph') }}" aria-label="{{ __('shell.search') }}">
    </form>

    <div class="controls">
        <a class="iconbtn search-mobile" href="{{ route('explore') }}" aria-label="{{ __('shell.search') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-3.5-3.5"/></svg>
        </a>

        @auth
            @php($unreadCount = auth()->user()->unreadNotifications()->count())
            <a class="iconbtn hide-mobile" href="{{ route('notifications.index') }}" aria-label="{{ __('social.notifications') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                @if ($unreadCount > 0)<span class="count">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
            </a>
            <a class="iconbtn hide-mobile" href="{{ route('cart.index') }}" aria-label="{{ __('shell.nav_cart') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.4 12.4a1.6 1.6 0 0 0 1.6 1.3h8.6a1.6 1.6 0 0 0 1.6-1.3L22 7H6"/></svg>
            </a>
        @endauth

        <details class="menu">
            <summary aria-label="{{ __('shell.account') }}">
                @auth
                    <span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                @else
                    <span class="iconbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></span>
                @endauth
            </summary>
            <div class="menu-pop">
                @auth
                    <a href="{{ auth()->user()->profileUrl() }}">{{ __('shell.nav_profile') }}</a>
                    <a href="{{ route('boards.index') }}">{{ __('social.my_boards') }}</a>
                    <a href="{{ route('orders.index') }}">{{ __('shell.nav_orders') }}</a>
                    <a href="{{ route('commissions.index') }}">{{ __('commissions.my_commissions') }}</a>
                    <a href="{{ route('notifications.index') }}">{{ __('social.notifications') }}</a>
                    <a href="{{ route('settings.profile.edit') }}">{{ __('shell.settings') }}</a>
                    @role('seller')
                        <div class="menu-sep"></div>
                        <a href="{{ route('create') }}">{{ __('shell.nav_create') }}</a>
                        <a href="{{ route('seller.orders.index') }}">{{ __('shell.nav_dash') }}</a>
                    @endrole
                    @role('admin')
                        <a href="/admin">{{ __('shell.nav_admin') }}</a>
                    @endrole
                    <div class="menu-sep"></div>
                @endauth
                <div class="menu-lang" aria-label="{{ __('shell.language') }}">
                    <a href="{{ request()->fullUrlWithQuery(['lang' => 'ar']) }}" @if (app()->getLocale() === 'ar') aria-current="true" @endif>العربية</a>
                    <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" @if (app()->getLocale() === 'en') aria-current="true" @endif>English</a>
                </div>
                <button type="button" onclick="toggleTheme()">{{ __('shell.theme') }}</button>
                @auth
                    <button type="submit" form="logout-form">{{ __('shell.logout') }}</button>
                @endauth
            </div>
        </details>

        @guest
            <a class="btn btn-dark btn-sm" href="{{ route('login') }}">{{ __('shell.login') }}</a>
        @endguest
    </div>
</header>

<main id="main-content">
    @yield('content')
</main>

<nav class="tabbar" aria-label="{{ __('shell.nav_main') }}">
    <a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5V21H3z"/></svg>{{ __('shell.nav_home') }}
    </a>
    <a href="{{ route('explore') }}" @if (request()->routeIs('explore')) aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-3.5-3.5"/></svg>{{ __('shell.nav_explore') }}
    </a>
    @auth
        <a href="{{ route('boards.index') }}" @if (request()->routeIs('boards.*')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="3" y="3" width="7" height="10" rx="1.5"/><rect x="14" y="3" width="7" height="6" rx="1.5"/><rect x="14" y="13" width="7" height="8" rx="1.5"/><rect x="3" y="17" width="7" height="4" rx="1.5"/></svg>{{ __('social.my_boards') }}
        </a>
        <a href="{{ route('cart.index') }}" @if (request()->routeIs('cart.*') || request()->routeIs('checkout.*')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.4 12.4a1.6 1.6 0 0 0 1.6 1.3h8.6a1.6 1.6 0 0 0 1.6-1.3L22 7H6"/></svg>{{ __('shell.nav_cart') }}
        </a>
        <a href="{{ auth()->user()->profileUrl() }}" @if (request()->routeIs('profile.show')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>{{ __('shell.nav_profile') }}
        </a>
    @endauth
</nav>

<form method="POST" action="{{ route('logout') }}" id="logout-form" hidden>@csrf</form>

<script>
function toggleTheme(){
    var root = document.documentElement;
    var current = root.dataset.theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    var next = current === 'dark' ? 'light' : 'dark';
    root.dataset.theme = next;
    document.cookie = 'theme=' + next + ';path=/;max-age=31536000;samesite=lax';
}
</script>
</body>
</html>
```

- [ ] **Step 7: Run the tests and build.**
  - Run: `php artisan test --compact` → all pass, including 4 new tests.
  - Run: `npm run build` → succeeds.
  - If `route('commissions.index')` or `route('settings.profile.edit')` throws `RouteNotFoundException`, check `php artisan route:list --name=commissions` and use the exact name. Both exist as of commit `e1226c2`.

- [ ] **Step 8: Visual check.** With `php artisan serve` running, open `/` and `/?lang=en` at 1440 px and at 375 px, in light and dark (toggle from the account menu). Confirm all of the following:
  - No horizontal scroll at 375 px, and the tab bar is visible.
  - The menu opens and closes.
  - In devtools (Elements → Computed), `color` vs `background-color` for `.sub` and `.btn-primary` is at least 4.5:1.

- [ ] **Step 9: Commit.**

```bash
git add resources/css/app.css resources/views/layouts/app.blade.php lang/ar/shell.php lang/en/shell.php tests/Feature/GalleryUiTest.php
git commit -F - <<'EOF'
feat(ui): gallery-wall tokens with OS-aware dark mode, top-bar shell, account menu, mobile tab bar

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
EOF
```

---

### Task 3: One gallery card, rendered from `PinPresenter` (spec §3.3 item 1, §5.1)

**Files:**
- Create: `app/Support/PinPresenter.php`
- Modify: `resources/views/partials/pin-card.blade.php` (full rewrite)
- Modify: `resources/views/pages/board.blade.php:17` and `resources/views/pages/explore.blade.php:67` (include call)
- Modify: `resources/css/app.css` (replace the `Piece cards` section)
- Modify: `lang/ar/shell.php`, `lang/en/shell.php` (`list_sep`)
- Test: `tests/Feature/GalleryUiTest.php`

**Interfaces:**
- Produces `App\Support\PinPresenter::present(Piece $piece, int $index): array`. It returns an array with exactly these keys:

  | Key | Type |
  |---|---|
  | `aspect` | string |
  | `bg` | string |
  | `font` | string |
  | `id` | int |
  | `img` | ?string |
  | `ink` | string |
  | `isReady` | bool |
  | `price` | string |
  | `scriptName` | string |
  | `sellerName` | string |
  | `sellerUrl` | string |
  | `size` | int |
  | `sold` | bool |
  | `text` | string |
  | `url` | string |
  | `weight` | int |

  The piece must have `seller`, `script` and `media` loaded (`FeedQuery` and `PiecesController` already eager-load them).
- Produces the partial `partials.pin-card`, which takes exactly `['pin' => array]`.
- Produces these CSS classes: `.pin-grid .work .frame .frame-art .typeset .wall-label .wl-title .wl-meta .wl-price .is-commission .is-sold`

- [ ] **Step 1: Write the failing tests.** Append to `tests/Feature/GalleryUiTest.php`. Add these `use` lines at the top of the file alongside the existing ones:

```php
use App\Domain\Catalog\Models\Piece;
use App\Domain\Discovery\Cache\FeedCache;
use App\Support\PinPresenter;
```

  Then append:

```php
function galleryPiece(array $overrides = []): Piece
{
    $seller = $overrides['seller'] ?? User::factory()->create();
    unset($overrides['seller']);

    return Piece::query()->create(array_merge([
        'seller_id' => $seller->id,
        'text' => 'سُبحانَ اللَّه',
        'normalized_text' => 'سبحان الله',
        'text_language' => 'ar',
        'listing_type' => 'ready',
        'price_minor' => 98_000,
        'state' => 'approved',
        'published_at' => now(),
    ], $overrides))->load(['seller', 'script', 'media']);
}

it('presents a piece as a flat card array', function () {
    $pin = PinPresenter::present(galleryPiece(), 0);

    expect(array_keys($pin))->toEqualCanonicalizing([
        'aspect', 'bg', 'font', 'id', 'img', 'ink', 'isReady', 'price', 'scriptName',
        'sellerName', 'sellerUrl', 'size', 'sold', 'text', 'url', 'weight',
    ]);
    expect($pin['aspect'])->toBeIn(['3 / 4', '1 / 1', '4 / 3']);
    expect($pin['img'])->toBeNull();
});

it('renders a typeset card without a duplicate title when there is no image', function () {
    $html = view('partials.pin-card', ['pin' => PinPresenter::present(galleryPiece(), 0)])->render();

    expect($html)->toContain('class="typeset"')
        ->not->toContain('onerror=')
        ->not->toContain('class="wl-title"');
});

it('falls back to typeset art when a card image fails to load', function () {
    $pin = array_merge(PinPresenter::present(galleryPiece(), 0), ['img' => '/storage/missing.jpg']);
    $html = view('partials.pin-card', ['pin' => $pin])->render();

    expect($html)->toContain('onerror="this.hidden=true;this.nextElementSibling.hidden=false"')
        ->toMatch('/<span class="typeset" hidden/')
        ->toContain('class="wl-title"');
});

it('labels sold and commission-only works honestly', function () {
    $soldPiece = galleryPiece();
    $soldPiece->forceFill(['sold_at' => now()])->save();
    $sold = PinPresenter::present($soldPiece, 0);
    $commission = PinPresenter::present(galleryPiece(['listing_type' => 'commission', 'price_minor' => null]), 1);

    expect($sold['sold'])->toBeTrue();
    expect($commission['isReady'])->toBeFalse();
    expect(view('partials.pin-card', ['pin' => $sold])->render())->toContain(__('commerce.sold'));
    expect(view('partials.pin-card', ['pin' => $commission])->render())->toContain(__('shell.t_commission'));
});
```

- [ ] **Step 2: Run them to verify they fail.**
  - Run: `php artisan test --compact --filter="card|presents"`
  - Expected: FAIL with `Class "App\Support\PinPresenter" not found`.

- [ ] **Step 3: Create the presenter.** Write `app/Support/PinPresenter.php`:

```php
<?php

namespace App\Support;

use App\Domain\Catalog\Models\Piece;

/**
 * Flattens a Piece into the plain array the gallery card renders.
 * Shared by the Livewire feed (which must not hold models in its payload) and Blade includes.
 * Expects seller, script and media to be eager-loaded.
 */
final class PinPresenter
{
    private const ASPECTS = ['3 / 4', '1 / 1', '4 / 3'];

    /** @return array<string, string|bool|int|null> */
    public static function present(Piece $piece, int $index): array
    {
        [$bg, $ink] = Palette::tint($index);

        $script = $piece->script;
        $scriptSlug = $script !== null ? $script->slug : 'naskh';
        $scriptName = '';
        if ($script !== null) {
            $localized = $script->{'name_'.app()->getLocale()};
            $scriptName = is_string($localized) ? $localized : '';
        }

        $pin = [
            'id' => $piece->id,
            'url' => route('pieces.show', $piece),
            'text' => $piece->text,
            'img' => $piece->coverMedia()?->derivativeUrl(480),
            'bg' => $bg,
            'ink' => $ink,
            'font' => Palette::font($scriptSlug),
            'weight' => Palette::weight($scriptSlug),
            'size' => max(22, min(54, (int) (220 / max(6, mb_strlen($piece->text)) + 14))),
            'aspect' => self::ASPECTS[$piece->id % count(self::ASPECTS)],
            'scriptName' => $scriptName,
            'sellerUrl' => $piece->seller->profileUrl(),
            'sellerName' => $piece->seller->name,
            'isReady' => $piece->isReady() && $piece->price_minor !== null,
            'sold' => $piece->sold_at !== null,
            'price' => Money::format($piece->price_minor),
        ];

        ksort($pin);

        return $pin;
    }
}
```

- [ ] **Step 4: Rewrite the card partial.** Replace `resources/views/partials/pin-card.blade.php` entirely:

```blade
{{-- Gallery-wall card: framed artwork + museum wall label. Takes $pin from App\Support\PinPresenter. --}}
<article class="work" wire:key="pin-{{ $pin['id'] }}">
    <a href="{{ $pin['url'] }}" class="frame" aria-label="{{ $pin['text'] }}">
        <span class="frame-art" style="aspect-ratio:{{ $pin['aspect'] }}">
            @if ($pin['img'])
                <img src="{{ $pin['img'] }}" alt="{{ $pin['text'] }}" loading="lazy" width="480" height="360"
                     onerror="this.hidden=true;this.nextElementSibling.hidden=false">
            @endif
            <span class="typeset" @if ($pin['img']) hidden @endif
                  style="background:{{ $pin['bg'] }};color:{{ $pin['ink'] }};font-family:{{ $pin['font'] }};font-weight:{{ $pin['weight'] }};font-size:min({{ $pin['size'] }}px, 14cqi)">{{ $pin['text'] }}</span>
        </span>
    </a>

    <div class="wall-label">
        @if ($pin['img'])
            <a href="{{ $pin['url'] }}" class="wl-title">{{ $pin['text'] }}</a>
        @endif
        <a href="{{ $pin['sellerUrl'] }}" class="wl-meta">{{ $pin['sellerName'] }}@if ($pin['scriptName'] !== ''){{ __('shell.list_sep') }}{{ $pin['scriptName'] }}@endif</a>
        @if ($pin['sold'])
            <span class="wl-price is-sold">{{ __('commerce.sold') }}</span>
        @elseif ($pin['isReady'])
            <span class="wl-price">{{ $pin['price'] }} {{ config('catalog.currency') }}</span>
        @else
            <span class="wl-price is-commission">{{ __('shell.t_commission') }}</span>
        @endif
    </div>
</article>
```

  Add the separator key. In `lang/ar/shell.php`, after `'language' => 'اللغة',`, add `'list_sep' => '، ',`. In `lang/en/shell.php`, after `'language' => 'Language',`, add `'list_sep' => ', ',`.

- [ ] **Step 5: Point the other includes at the presenter.**
  - In `resources/views/pages/board.blade.php` line 17, replace `@include('partials.pin-card', ['piece' => $piece, 'index' => $loop->index])` with:

```blade
@include('partials.pin-card', ['pin' => \App\Support\PinPresenter::present($piece, $loop->index)])
```

  - In `resources/views/pages/explore.blade.php` line 67, make the same replacement (the variable there is also `$piece`).
  - `pages/piece.blade.php` is rewritten in Task 6. Until then, change its line 149 from `@include('partials.pin-card', ['piece' => $relatedPiece, 'index' => $loop->index])` to:

```blade
@include('partials.pin-card', ['pin' => \App\Support\PinPresenter::present($relatedPiece, $loop->index)])
```

  - Check that the board controller eager-loads the presenter's relations. Run `grep -rn "with(\[" app/Http/Controllers/Social`. If the board's pieces query lacks `'seller'`, `'script'` or `'media'`, add the missing names to its `with([...])` array. Without them, each card would run lazy queries.

- [ ] **Step 6: Replace the card CSS.** In `resources/css/app.css`, delete everything from `/* ---------- Piece cards — manuscript folio / gallery plaque ---------- */` through the end of the `.pcard-price small{…}` rule. In its place, put:

```css
/* ---------- Gallery wall: masonry of framed works ---------- */
/* ponytail: CSS columns flow top-to-bottom, so "load more" items land at each column's foot — accepted for discovery. */
.pin-grid{columns:4 240px;column-gap:clamp(20px,2.6vw,40px)}
.work{display:block;break-inside:avoid;margin:0 0 clamp(24px,3vw,40px)}
.frame{
    display:block;background:var(--surface);padding:clamp(8px,1.2vw,16px);box-shadow:var(--shadow-hang);
    transition:transform .25s var(--ease),box-shadow .25s var(--ease);
}
.work:hover .frame,.frame:focus-visible{transform:translateY(-3px);box-shadow:var(--shadow-2)}
.frame-art{display:block;position:relative;overflow:hidden;background:var(--surface-2);container-type:inline-size}
.frame-art img{display:block;width:100%;height:100%;object-fit:cover}
.typeset{
    position:absolute;inset:0;display:grid;place-items:center;text-align:center;
    padding:12%;line-height:1.5;word-break:break-word;
}
.frame-art img[hidden],.typeset[hidden]{display:none}
.wall-label{
    display:grid;gap:1px;margin-top:12px;padding-inline-start:10px;
    border-inline-start:2px solid var(--ink);font-size:var(--t-sm);line-height:1.5;
}
.wl-title{font-family:var(--f-disp);font-size:16px;font-weight:700;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.wl-meta{font-size:var(--t-xs);color:var(--ink-faint);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.wl-meta:hover{color:var(--ink)}
.wl-price{font-weight:600;color:var(--ink)}
.wl-price.is-commission{color:var(--primary)}
.wl-price.is-sold{color:var(--gold)}
@media(max-width:859px){
    .pin-grid{columns:2;column-gap:14px}
    .work{margin-bottom:18px}
    .frame{padding:6px}
}
```

  Then search the rest of the file for orphans: run `grep -n "pcard\|scr-seal" resources/css/app.css resources/views -r`. Delete any remaining `.pcard*`/`.scr-seal` CSS rules. View hits should be zero after this task; if `profile.blade.php` still references `.pcard`, switch it to the partial in the same way as Step 5.

- [ ] **Step 7: Run the tests and build.**
  - Run: `php artisan test --compact` → all pass, including the existing `keeps feed query count flat and eager loaded`.
  - Run: `npm run build` → succeeds.

- [ ] **Step 8: Commit.**

```bash
git add app/Support/PinPresenter.php resources/views/partials/pin-card.blade.php resources/views/pages resources/css/app.css lang tests/Feature/GalleryUiTest.php app/Http/Controllers
git commit -F - <<'EOF'
feat(ui): single gallery card (frame + wall label) via PinPresenter, broken-image typeset fallback

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
EOF
```

---

### Task 4: The feed uses the card; masonry, skeletons and an empty state (spec §5.2, §5.6)

**Files:**
- Modify: `app/Livewire/Feed.php` (`toPins` and imports)
- Modify: `resources/views/livewire/feed.blade.php` (full rewrite)
- Modify: `resources/css/app.css` (append the skeleton rules)
- Test: `tests/Feature/GalleryUiTest.php`

**Interfaces:**
- Consumes `PinPresenter::present()` and `partials.pin-card` from Task 3.
- Produces the `.skel` CSS class.

- [ ] **Step 1: Write the failing tests.** Append:

```php
it('renders the home feed as a single masonry grid of gallery cards', function () {
    galleryPiece();
    FeedCache::flush();

    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'class="pin-grid"'))->toBe(1);
    expect($html)->toContain('class="wall-label"')->not->toContain('class="pcard"');
});

it('tells visitors what to expect when the feed is empty', function () {
    FeedCache::flush();

    $this->get('/')->assertOk()->assertSee(__('shell.empty_feed_title'));
});
```

- [ ] **Step 2: Run them to verify they fail.**
  - Run: `php artisan test --compact --filter="masonry|feed is empty"`
  - Expected: FAIL. The feed still renders its own `pcard` markup and has no empty state.

- [ ] **Step 3: Simplify `Feed::toPins`.** In `app/Livewire/Feed.php`, replace the whole `toPins` method body:

```php
    private function toPins(iterable $pieces): array
    {
        $rendered = [];

        foreach ($pieces as $piece) {
            $rendered[] = PinPresenter::present($piece, count($rendered));
        }

        return $rendered;
    }
```

  In the imports, remove `use App\Support\Money;` and `use App\Support\Palette;`, and add `use App\Support\PinPresenter;`. Keep the method's docblock.

- [ ] **Step 4: Rewrite the feed view.** Replace `resources/views/livewire/feed.blade.php` entirely:

```blade
<div wire:key="feed-{{ app()->getLocale() }}">
    @if ($pins === [])
        <div class="empty-state">
            <div class="art">خَطّ</div>
            <h2 class="h1" style="font-size:var(--t-lg)">{{ __('shell.empty_feed_title') }}</h2>
            <p class="sub" style="margin:0">{{ __('shell.empty_feed_sub') }}</p>
        </div>
    @else
        <div class="pin-grid">
            @foreach ($pins as $pin)
                @include('partials.pin-card', ['pin' => $pin])
            @endforeach

            @for ($i = 0; $i < 3; $i++)
                <div class="skel" wire:loading.block wire:target="loadMore" aria-hidden="true"><span></span></div>
            @endfor
        </div>
    @endif

    @if ($hasMore)
        <div style="text-align:center;padding:10px 0 30px">
            <button type="button" class="btn btn-outline" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore">
                {{ __('shell.load_more') }}
            </button>
        </div>
    @endif
</div>
```

- [ ] **Step 5: Add the skeleton CSS.** Append after the gallery wall block in `resources/css/app.css`:

```css
.skel{break-inside:avoid;margin:0 0 clamp(24px,3vw,40px);background:var(--surface);padding:clamp(8px,1.2vw,16px);box-shadow:var(--shadow-hang)}
.skel span{display:block;aspect-ratio:4 / 3;background:var(--surface-2);animation:skel-pulse 1.2s ease-in-out infinite alternate}
@keyframes skel-pulse{from{opacity:1}to{opacity:.45}}
```

  The existing global `prefers-reduced-motion` rule already disables the animation.

- [ ] **Step 6: Run the tests and build.**
  - Run: `php artisan test --compact` → all pass.
  - Run: `npm run build` → succeeds.
  - Visual check: at 1440 px, `/` shows 4 masonry columns with mixed card heights. Clicking "Load more" briefly shows the skeletons, then the new cards. At 375 px there are 2 columns.

- [ ] **Step 7: Commit.**

```bash
git add app/Livewire/Feed.php resources/views/livewire/feed.blade.php resources/css/app.css tests/Feature/GalleryUiTest.php
git commit -F - <<'EOF'
feat(ui): masonry feed of gallery cards with skeleton loading and empty state

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
EOF
```

---

### Task 5: Home hero and script ribbon (spec §3.3 item 2, §5.3)

**Files:**
- Modify: `app/Http/Controllers/HomeController.php`
- Create: `resources/views/partials/script-ribbon.blade.php`
- Modify: `resources/views/pages/home.blade.php` (full rewrite)
- Modify: `resources/css/app.css` (append the hero and ribbon rules; delete the `Chips` section if `grep -rn 'class="chip' resources/views` finds no other users)
- Test: `tests/Feature/GalleryUiTest.php`

**Interfaces:**
- Consumes `FeedQuery::firstPage(): Collection<int, Piece>` (cached, 24 items), `TaxonomyRepository::all()['scripts']` (a `Collection<int, Script>` where each `Script` has `id`, `slug`, `name_ar` and `name_en`), `PinPresenter::present()`, and `Palette::font(string)` / `Palette::weight(string)`.
- Produces the partial `partials.script-ribbon`, which takes `['scripts' => Collection, 'activeId' => ?int]`. The phase 4 explore plan will reuse it.

- [ ] **Step 1: Write the failing tests.** Append the following, and add `use Database\Seeders\CatalogSeeder;` and `use App\Domain\Catalog\Models\Script;` at the top:

```php
it('shows a script ribbon that links each script, set in its own face, into explore', function () {
    $this->seed(CatalogSeeder::class);
    $script = Script::query()->orderBy('sort')->firstOrFail();

    $this->get('/')
        ->assertOk()
        ->assertSee('class="ribbon"', false)
        ->assertSee(route('explore', ['script' => $script->id]), false)
        ->assertSee($script->name_ar);
});

it('features the newest piece in the home hero', function () {
    galleryPiece(['text' => 'إِنَّ مَعَ العُسرِ يُسرًا', 'normalized_text' => 'ان مع العسر يسرا']);
    FeedCache::flush();

    $this->get('/')->assertOk()->assertSee('class="hero-feature"', false);
});

it('renders the home page with no featured piece when the catalog is empty', function () {
    FeedCache::flush();

    $this->get('/')
        ->assertOk()
        ->assertSee('لوحاتٌ من نور الحرف')
        ->assertDontSee('class="hero-feature"', false);
});
```

- [ ] **Step 2: Run them to verify they fail.**
  - Run: `php artisan test --compact --filter="ribbon|hero|no featured"`
  - Expected: the ribbon and featured tests FAIL. The "no featured" test may already pass; that's fine, because it guards the null path.

- [ ] **Step 3: Pass the data from the controller.** Replace `app/Http/Controllers/HomeController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Domain\Catalog\Support\TaxonomyRepository;
use App\Domain\Discovery\Queries\FeedQuery;
use App\Support\PinPresenter;
use Illuminate\View\View;

final class HomeController extends Controller
{
    public function index(FeedQuery $feed, TaxonomyRepository $taxonomy): View
    {
        // Same cached first page the Livewire feed renders: no extra query on a warm cache.
        $newest = $feed->firstPage()->first();

        return view('pages.home', [
            'featured' => $newest !== null ? PinPresenter::present($newest, 0) : null,
            'scripts' => $taxonomy->all()['scripts'],
        ]);
    }
}
```

- [ ] **Step 4: Create the ribbon partial.** Write `resources/views/partials/script-ribbon.blade.php`:

```blade
{{-- Script ribbon: each script's name set in its own calligraphic face; every tile is a real explore link. --}}
<nav class="ribbon" aria-label="{{ __('shell.f_script') }}">
    <a href="{{ route('explore') }}" class="ribbon-all" @if ($activeId === null) aria-current="true" @endif>{{ __('shell.all') }}</a>
    @foreach ($scripts as $script)
        <a href="{{ route('explore', ['script' => $script->id]) }}"
           @if ($activeId === (int) $script->id) aria-current="true" @endif
           style="font-family:{{ \App\Support\Palette::font($script->slug) }};font-weight:{{ \App\Support\Palette::weight($script->slug) }}">{{ $script->{'name_'.app()->getLocale()} }}</a>
    @endforeach
</nav>
```

- [ ] **Step 5: Rewrite the home page.** Replace `resources/views/pages/home.blade.php` entirely:

```blade
@extends('layouts.app')

@section('title', __('shell.home_title'))

@section('content')
<section class="page">
    <div class="hero">
        <div>
            <h1>{{ __('shell.home_title') }}</h1>
            <p>{{ __('shell.home_sub') }}</p>
        </div>
        @if ($featured !== null)
            <div class="hero-feature">
                @include('partials.pin-card', ['pin' => array_merge($featured, ['aspect' => '4 / 3'])])
            </div>
        @endif
    </div>

    @include('partials.script-ribbon', ['scripts' => $scripts, 'activeId' => null])

    @livewire('feed')
</section>
@endsection
```

- [ ] **Step 6: Add the hero and ribbon CSS.** Append:

```css
/* ---------- Home: hero + script ribbon ---------- */
.hero{display:grid;grid-template-columns:1.1fr .9fr;gap:clamp(24px,5vw,80px);align-items:end;margin-bottom:clamp(28px,4vw,48px)}
.hero h1{font-family:var(--f-disp);font-size:var(--t-hero);font-weight:700;line-height:1.15;margin:0}
.hero p{color:var(--ink-soft);max-width:46ch;margin:14px 0 0;font-size:16px}
.hero-feature .work{margin:0}
.ribbon{display:flex;gap:10px;overflow-x:auto;scrollbar-width:none;padding:2px 2px 8px;margin-bottom:clamp(24px,3vw,40px)}
.ribbon::-webkit-scrollbar{display:none}
.ribbon a{
    flex:0 0 auto;min-width:112px;padding:8px 18px;text-align:center;font-size:26px;line-height:1.5;
    background:var(--surface);color:var(--ink);box-shadow:var(--shadow-1);
}
.ribbon a:hover{background:var(--surface-2)}
.ribbon a[aria-current]{background:var(--ink);color:var(--surface)}
.ribbon .ribbon-all{font-size:var(--t-base);font-weight:600;display:grid;place-items:center}
@media(max-width:859px){.hero{grid-template-columns:1fr}}
```

  If `grep -rn 'class="chip' resources/views` returns nothing, delete the `/* ---------- Chips ---------- */` section (`.chips`, `.chip`, `.chip:hover`, `.chip.on`).

- [ ] **Step 7: Run the tests and build.**
  - Run: `php artisan test --compact` → all pass, including `ShellTest` ("renders the themed bilingual shell" still finds `لوحاتٌ من نور الحرف`).
  - Run: `npm run build` → succeeds.
  - Visual check, ar and en, 1440 and 375:
    - The headline is large Amiri.
    - The featured work is framed on the inline-end side (stacked on mobile).
    - Ribbon tiles show ثلث / ديواني / كوفي… in their own faces, and clicking one opens explore filtered to it.

- [ ] **Step 8: Commit.**

```bash
git add app/Http/Controllers/HomeController.php resources/views/partials/script-ribbon.blade.php resources/views/pages/home.blade.php resources/css/app.css tests/Feature/GalleryUiTest.php
git commit -F - <<'EOF'
feat(ui): home hero with featured work and script ribbon linking into explore (replaces dead chips)

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
EOF
```

---

### Task 6: Piece page (spec §5.4)

**Files:**
- Modify: `resources/views/pages/piece.blade.php` (full rewrite)
- Modify: `resources/css/app.css` (append the piece rules)
- Modify: `lang/ar/shell.php`, `lang/en/shell.php` (`request_similar`, `cm`)
- Test: `tests/Feature/GalleryUiTest.php`

**Interfaces:**
- Consumes `PinPresenter::present()`, `partials.pin-card` and the controller's existing variables: `$piece` (with `script`, `medium`, `theme`, `color` and `seller` loaded; `media` lazy), `$related`, `$topLevel` and `$repliesByParent`.
- Consumes these routes: `cart.add`, `login`, `commissions.create` (param: seller `User`), `comments.store`, `reports.store`.
- Consumes these Livewire components: `FollowButton(profile: User)`, `LikeButton(piece)`, `SaveToBoardButton(piece)`.

- [ ] **Step 1: Write the failing tests.** Append:

```php
it('offers add-to-cart as the primary action and a similar commission as secondary', function () {
    $piece = galleryPiece();

    $this->get(route('pieces.show', $piece))
        ->assertOk()
        ->assertSee(__('commerce.add_to_cart'))
        ->assertSee(route('commissions.create', $piece->seller), false)
        ->assertSee('class="frame"', false);
});

it('makes a similar commission the primary action once a piece is sold', function () {
    $piece = galleryPiece();
    $piece->forceFill(['sold_at' => now()])->save();

    $this->get(route('pieces.show', $piece))
        ->assertOk()
        ->assertSee(__('commerce.sold'))
        ->assertDontSee(route('cart.add', $piece), false)
        ->assertSee('btn btn-block btn-primary" href="'.route('commissions.create', $piece->seller), false);
});

it('hides follow and request-similar from the seller on their own piece', function () {
    $piece = galleryPiece();

    $this->actingAs($piece->seller)
        ->get(route('pieces.show', $piece))
        ->assertOk()
        ->assertDontSee(route('commissions.create', $piece->seller), false)
        ->assertDontSee(__('shell.follow'));
});
```

- [ ] **Step 2: Run them to verify they fail.**
  - Run: `php artisan test --compact --filter="primary action|own piece"`
  - Expected: FAIL. There is no `commissions.create` link and no `.frame` on the page today.

- [ ] **Step 3: Add lang keys.**
  - `lang/ar/shell.php`, after `'list_sep' => '، ',`:

```php
    'request_similar' => 'اطلب قطعة مشابهة',
    'cm' => 'سم',
```

  - `lang/en/shell.php`, after `'list_sep' => ', ',`:

```php
    'request_similar' => 'Request a similar piece',
    'cm' => 'cm',
```

- [ ] **Step 4: Rewrite the piece page.** Replace `resources/views/pages/piece.blade.php` entirely:

```blade
@extends('layouts.app')

@section('title', $piece->text)

@section('content')
@php
    $locale = app()->getLocale();
    $pin = \App\Support\PinPresenter::present($piece, 0);
    $large = $piece->coverMedia()?->derivativeUrl(960) ?? $pin['img'];
    $sold = $piece->sold_at !== null;
    $buyable = $piece->isReady() && $piece->price_minor !== null && ! $sold;
    $isOwner = auth()->check() && (int) auth()->id() === (int) $piece->seller_id;
@endphp
<section class="page">
    <div class="piece">
        <div>
            <div class="frame">
                <span class="frame-art" style="aspect-ratio:4 / 3">
                    @if ($large)
                        <img src="{{ $large }}" alt="{{ $piece->text }}" width="960" height="720"
                             onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                    @endif
                    <span class="typeset" @if ($large) hidden @endif
                          style="background:{{ $pin['bg'] }};color:{{ $pin['ink'] }};font-family:{{ $pin['font'] }};font-weight:{{ $pin['weight'] }};font-size:min({{ $pin['size'] * 2 }}px, 12cqi)">{{ $piece->text }}</span>
                </span>
            </div>

            @if ($piece->media->count() > 1)
                <div class="thumbs">
                    @foreach ($piece->media as $medium)
                        <img src="{{ $medium->derivativeUrl(480) ?? '' }}" alt="" loading="lazy" width="480" height="360">
                    @endforeach
                </div>
            @endif
        </div>

        <div class="piece-info">
            <h1>{{ $piece->text }}</h1>

            <div class="artist">
                <a href="{{ $piece->seller->profileUrl() }}" class="dot" aria-hidden="true" tabindex="-1">{{ mb_substr($piece->seller->name, 0, 1) }}</a>
                <a href="{{ $piece->seller->profileUrl() }}">
                    <span class="n">{{ $piece->seller->name }}</span><br>
                    <span class="u">{{ '@'.$piece->seller->username }}</span>
                </a>
                @unless ($isOwner)
                    @livewire(\App\Livewire\FollowButton::class, ['profile' => $piece->seller], key('follow-'.$piece->seller_id))
                @endunless
            </div>

            @if ($buyable)
                <div class="price">{{ \App\Support\Money::format($piece->price_minor) }} <small>{{ config('catalog.currency') }}</small></div>
            @elseif ($sold)
                <span class="badge badge-gold">{{ __('commerce.sold') }}</span>
            @endif

            <div class="actions">
                @if ($buyable)
                    @auth
                        <form method="POST" action="{{ route('cart.add', $piece) }}">
                            @csrf
                            <button type="submit" class="btn btn-block btn-primary">{{ __('commerce.add_to_cart') }}</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-block btn-primary">{{ __('commerce.add_to_cart') }}</a>
                    @endauth
                @endif
                @unless ($isOwner)
                    <a class="btn btn-block {{ $buyable ? 'btn-outline' : 'btn-primary' }}" href="{{ route('commissions.create', $piece->seller) }}">{{ __('shell.request_similar') }}</a>
                @endunless
            </div>

            <dl class="specs">
                @if ($piece->script)
                    <dt>{{ __('shell.f_script') }}</dt><dd>{{ $piece->script->{'name_'.$locale} }}</dd>
                @endif
                @if ($piece->medium)
                    <dt>{{ __('catalog.medium') }}</dt><dd>{{ $piece->medium->{'name_'.$locale} }}</dd>
                @endif
                @if ($piece->width_cm)
                    <dt>{{ __('catalog.width') }} / {{ __('catalog.height') }}</dt><dd>{{ $piece->width_cm }} × {{ $piece->height_cm }} {{ __('shell.cm') }}</dd>
                @endif
                @if ($piece->theme)
                    <dt>{{ __('shell.f_theme') }}</dt><dd>{{ $piece->theme->{'name_'.$locale} }}</dd>
                @endif
                @if ($piece->transliteration)
                    <dt>{{ __('catalog.transliteration') }}</dt><dd dir="ltr">{{ $piece->transliteration }}</dd>
                @endif
                <dt>{{ __('shell.d_avail') }}</dt><dd>{{ $piece->isReady() ? __('shell.t_ready') : __('shell.t_commission') }}</dd>
            </dl>

            @if ($piece->description)
                <p class="muted" style="white-space:pre-line;margin:18px 0 0">{{ $piece->description }}</p>
            @endif

            <div class="social-row">
                @livewire(\App\Livewire\LikeButton::class, ['piece' => $piece], key('like-'.$piece->id))
                @livewire(\App\Livewire\SaveToBoardButton::class, ['piece' => $piece], key('save-'.$piece->id))
            </div>
        </div>
    </div>

    <div class="sec-title">
        <h3>{{ __('social.comments') }}</h3>
        <span class="badge badge-soft">{{ $topLevel->count() + $repliesByParent->sum(fn ($replies) => $replies->count()) }}</span>
        @auth
            <details style="margin-inline-start:auto">
                <summary class="muted" style="cursor:pointer;font-size:var(--t-xs)">{{ __('reviews.report') }}</summary>
                <form method="POST" action="{{ route('reports.store') }}" style="margin-top:10px;max-width:420px" onsubmit="this.querySelector('button').disabled=true">
                    @csrf
                    <input type="hidden" name="reportable_type" value="piece">
                    <input type="hidden" name="reportable_id" value="{{ $piece->id }}">
                    <select name="reason" class="input" required>
                        @foreach (['inappropriate', 'offensive', 'copyright', 'scam', 'other'] as $reason)
                            <option value="{{ $reason }}">{{ __("reports.reason_$reason") }}</option>
                        @endforeach
                    </select>
                    <textarea name="note" class="input" style="margin-top:8px" maxlength="1000"></textarea>
                    <button type="submit" class="btn btn-outline btn-sm" style="margin-top:8px">{{ __('reviews.report') }}</button>
                </form>
            </details>
        @endauth
    </div>
    <div class="panel">
        @forelse ($topLevel as $comment)
            @include('partials.comment', ['comment' => $comment])
        @empty
            <p class="muted" style="margin:0">{{ __('social.no_comments') }}</p>
        @endforelse

        @auth
            <form method="POST" action="{{ route('comments.store', $piece) }}" style="margin-top:16px">
                @csrf
                <textarea name="body" required minlength="2" maxlength="2000" placeholder="{{ __('social.add_comment') }}" aria-label="{{ __('social.add_comment') }}"></textarea>
                @error('body')<div class="err">{{ $message }}</div>@enderror
                <div style="text-align:end;margin-top:10px">
                    <button type="submit" class="btn btn-dark btn-sm">{{ __('shell.send') }}</button>
                </div>
            </form>
        @else
            <p class="muted" style="margin:16px 0 0;text-align:center">
                <a href="{{ route('login') }}" style="color:var(--primary-ink);font-weight:700">{{ __('shell.login') }}</a> {{ __('social.to_comment') }}
            </p>
        @endauth
    </div>

    @if ($related->isNotEmpty())
        <div class="sec-title"><h3>{{ __('shell.related') }}</h3></div>
        <div class="pin-grid">
            @foreach ($related as $relatedPiece)
                @include('partials.pin-card', ['pin' => \App\Support\PinPresenter::present($relatedPiece, $loop->index)])
            @endforeach
        </div>
    @endif
</section>
@endsection
```

  > **Behaviour change to check:** `DiscoveryTest` "shows approved piece detail publicly" asserts `__('shell.related')` is visible. If the piece in that test has **no** related pieces, the new `@if ($related->isNotEmpty())` would hide the heading and fail the test. In that case, keep the heading unconditional and show `{{ __('shell.no_results') }}` in an `empty-state` when the list is empty. Don't weaken the test.
  >
  > The 🚩 and ✔ emoji from the old view are dropped, per the brief's "no emoji in the UI".

- [ ] **Step 5: Add the piece CSS.** Append:

```css
/* ---------- Piece page ---------- */
.piece{display:grid;grid-template-columns:1.35fr 1fr;gap:clamp(24px,5vw,72px);align-items:start}
.piece .frame{padding:clamp(16px,3vw,40px)}
.piece .work:hover .frame{transform:none}
.thumbs{display:flex;gap:10px;margin-top:14px;flex-wrap:wrap}
.thumbs img{width:110px;height:82px;object-fit:cover;box-shadow:var(--shadow-1)}
.piece-info h1{font-family:var(--f-disp);font-size:var(--t-xl);font-weight:700;line-height:1.3;margin:0}
.artist{display:flex;align-items:center;gap:12px;margin:18px 0 22px}
.artist .n{font-weight:600}
.artist .u{font-size:var(--t-xs);color:var(--ink-faint)}
.artist .follow-wrap{margin-inline-start:auto}
.price{font-family:var(--f-lat);font-size:30px;font-weight:600;line-height:1.2}
.price small{font-size:var(--t-sm);font-weight:400;color:var(--ink-faint)}
.actions{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin:16px 0 24px}
.specs{display:grid;grid-template-columns:auto 1fr;gap:10px 24px;margin:0;padding-top:18px;border-top:1px solid var(--line);font-size:var(--t-sm)}
.specs dt{color:var(--ink-faint)}
.specs dd{margin:0}
.social-row{display:flex;gap:10px;margin-top:20px}
@media(max-width:859px){.piece{grid-template-columns:1fr}}
```

  Then delete the now-unused `.grid2`, `.kv*` and `.canvas*` rules **only if** `grep -rn 'grid2\|class="kv\|class="canvas' resources/views` returns nothing. Other pages (cart, order, commission) may still use them until phase 4–7.

- [ ] **Step 6: Run the tests and build.**
  - Run: `php artisan test --compact` → all pass.
  - Run: `npm run build` → succeeds.
  - Visual check, as a guest, a buyer and the seller, in ar/en and at 1440/375:
    - The large framed work is on the inline-start side, with the info column next to it.
    - There is exactly one teal primary button.
    - A sold piece shows the gold "Sold" badge and "Request a similar piece" as the primary action.
    - The seller sees neither Follow nor Request.

- [ ] **Step 7: Commit.**

```bash
git add resources/views/pages/piece.blade.php resources/css/app.css lang tests/Feature/GalleryUiTest.php
git commit -F - <<'EOF'
feat(ui): gallery piece page — framed work, artist block, one primary action, request-similar commission

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
EOF
```

---

### Task 7: Phase gate (spec §7)

**Files:** none changed unless a check fails.

- [ ] **Step 1: Full test suite and build.**
  - Run: `php artisan test --compact` → all pass. Record the count; it should be 64 + 17 new = 81.
  - Run: `npm run build` → succeeds.
  - Run: `vendor/bin/pint --test` → passes. If it doesn't, run `vendor/bin/pint` and commit with `style: pint`.
  - Run: `vendor/bin/phpstan analyse --memory-limit=1G` → no new errors in `app/Support/PinPresenter.php`, `app/Livewire/Feed.php` or `app/Http/Controllers/HomeController.php`.

- [ ] **Step 2: Screenshots.** Take screenshots of `/`, `/pieces/{a ready piece}`, `/pieces/{a sold piece}` and `/explore` in each combination below (32 in total). Do it in the Browser pane with `resize_window` and `colorScheme`:
  - ar and en
  - light and dark
  - 375 px and 1440 px

  Check each one for:
  - horizontal scroll at 375 px
  - clipped text
  - an unreadable gold or faint label
  - a visible focus ring when tabbing through the top bar, the menu and a card

- [ ] **Step 3: Review skills.** Run `baseline-ui`, then `fixing-accessibility`, then `fixing-motion-performance`, scoped to the files changed in Tasks 2–6. Fix any confirmed findings in a single `fix(ui): review findings` commit, with tests and the build re-run green.

- [ ] **Step 4: Report.** Summarise for the user:
  - the test count
  - which audit findings E1–E10 are now closed (E1–E7 and E9's gold contrast should be)
  - links to the screenshots
  - the three spec deviations listed at the top of this plan

  Then ask for go-ahead to write the phase 4–7 plan.
