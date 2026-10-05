# Verdict: REDESIGN

The front end scores 14/30, which is below the REFINE threshold of 20. Discovery is not usable (dead search and filters), and the shell breaks on desktop and mobile. That means rebuilding the experience from its purpose, while keeping the brand tokens and the per-script typeset idea.

**Note:** about a third of the lost points come from four cheap defects (E1–E4). Fixing them first would raise #3, #4 and #8. The verdict still follows the scorecard rule. Even with those fixes, the missing mobile shell, the dead discovery path and the generic layout keep the total below 20.

## Highest-leverage moves
1. **#3 Aesthetic / #8 Thorough:** fix the foundation first. Restore `<!DOCTYPE html>` (`layouts/app.blade.php:1`), remove the nested `.pin-grid` (`pages/home.blade.php:28`), make image URLs relative or align `APP_URL` (`.env:5`), and fall back to the typeset art when an image fails to load.
2. **#2 Useful / #6 Honest:** make discovery real. Remove the duplicate `/explore` placeholder route (`routes/web.php:45`), and turn the home chips into working filter links (`pages/home.blade.php:22`).
3. **#5 Unobtrusive:** design a mobile-first shell. Use a bottom tab bar under 760 px, a compact topbar with a search button, and controls inside a menu (E7).
4. **#4 Understandable:** use the teal brand color for primary buttons and keep `--danger` for destructive actions only (`app.css:215`). Fix the gold seal contrast (2.70:1 → ≥4.5:1).
5. **#1 Innovative / #10 Less:** make the artwork the card. Make the typeset art the hero of each card, remove the duplicated title, and build the editorial "gallery, not a shop" direction from `UI-UPGRADE-PROMPT.md`.
