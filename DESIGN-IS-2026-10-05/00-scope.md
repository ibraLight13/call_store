# Scope

- **Audited:** Khatt consumer front end, live at `http://127.0.0.1:8000/`, using `php artisan serve` and the demo seeder data.
- **Screens:** home feed (`/`), piece page (`/pieces/1`), explore (`/explore`), login (`/login`), 404 page, and mobile home in English.
- **Viewports:** 1440×900 desktop and 375×812 mobile. Dark mode was emulated with `prefers-color-scheme: dark`.
- **Source files:** `resources/views/layouts/app.blade.php`, `resources/views/pages/*.blade.php`, `resources/views/livewire/feed.blade.php`, `resources/css/app.css`, `routes/web.php`, `.env`.
- **Primary user:** a buyer who wants Arabic calligraphy, either a ready-made piece or a custom commission.
- **Primary task:** discover a piece, open it, then buy it or request a commission.
- **Constraints:** Laravel 13, Blade, Livewire 3 and Tailwind 4; RTL-first with ar and en; light and dark themes; no new JS framework; the Filament panels are out of scope. The full brief is in `UI-UPGRADE-PROMPT.md`.
- **Reference:** the approved prototype `khatt-calligraphy-prototype (1).html`.
- **Not inspected:** logged-in flows (boards, cart, checkout, commission workspace, seller pages), because there was no session during this pass. Phase 1 of the plan should cover them.
