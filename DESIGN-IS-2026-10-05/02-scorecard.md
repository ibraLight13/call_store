# Scorecard (Dieter Rams)

1. **Innovative: 1/3**
   - Evidence: E2, E11. The layout is a standard Pinterest grid. The typeset-per-script fallback is the only original idea, and it is undermined by the broken images (E3).
   - Why this score: a refreshed pattern would earn 2, but what currently ships imitates the reference with minor variation.

2. **Useful: 1/3**
   - Evidence: E4, E5. Search and explore lead to "coming soon", and the filter chips do nothing.
   - Why this score: the feed and piece page still allow buying, so the task is supported (not 0), but only by detours.

3. **Aesthetic: 1/3**
   - Evidence: E1, E2, E3, E9. Quirks mode shows the raw DOCTYPE text, the feed is a single column on a 1440 px canvas, images are broken, and there are 12 font sizes.
   - Why this score: the token system exists, which keeps it above 0, but the single-column feed is a jarring violation.

4. **Understandable: 1/3**
   - Evidence: E5, E6. The chips look actionable but are inert, and the main "Sign in" button is styled as a destructive action.
   - Why this score: two to three controls mislead the user.

5. **Unobtrusive: 1/3**
   - Evidence: E7, E10. On mobile, the rail and the overflowing topbar take more room than the art, and each card repeats its title under the artwork.
   - Why this score: scored on the worst instance (mobile), where the decoration competes with the content.

6. **Honest: 1/3**
   - Evidence: E4, E5. The labels don't match the behaviour: "Search" leads to a placeholder, and the filter chips do nothing. No dark patterns were found.
   - Why this score: there are two label-to-behaviour mismatches.

7. **Long-lasting: 3/3**
   - Evidence: E11. Warm paper, serif display type and restrained teal and gold, with no fad gradients or trend typography.
   - Why this score: there are no dated markers.

8. **Thorough: 1/3**
   - Evidence: E3, E8, E9. There is no broken-image state, the loading state is just "…", there are no skeletons, dark mode ignores the OS setting, and mobile overflows.
   - Why this score: two to three states are missing or rough.

9. **Environmentally friendly: 2/3**
   - Evidence: E10. 447 KB transferred, no idle motion and reduced motion respected, but 5 web-font families load and the OS dark preference is not used (E8).
   - Why this score: dark mode is available through the toggle, so it is not 0, and the payload is under 500 KB.

10. **As little design as possible: 2/3**
    - Evidence: E5, E10. The 9 dead chips and the duplicated card title can be removed.
    - Why this score: there are two removable element types.

**Total: 14/30.** No principle scored 0. Load-bearing principles #2, #4 and #6 each scored 1.
