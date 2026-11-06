# Development and releases

## Source map

- `index.html`: markup, base CSS and core browser/data/navigation code.
- `classic.css`: shared classic styling and bundled font declaration.
- `classic-ui.js`: accessible green menus, news scrollbar and browser-only tactics annotations.
- `ui-rules.js`, `tests/ui-rules.cjs`: pure news selection, financial formatting and table separator rules with isolated checks.
- `assets/`: bundled Jost font/licence and original profile pointer.
- `images/backgrounds/` and `splash.png`: presentation assets.
- `api.php`: read-only FPL/news adapters, normalization, scoring, API routing.
- `runtime.php`: validated settings, private cache path, league/season isolation, stale-cache handling.
- `config.example.json`: public demo settings; private override is config.local.json or FPL_CONFIG_FILE.
- `tests/regression.php`: isolated regression fixtures, not public demo content.
- `tools/check.py`, `tools/package.py`: local checks and allowlisted release packaging.

- `league-selector.js`: themed visitor dialog, ID/link parsing, local browser storage and API request decoration.

## Checks

With PHP 8.3+, Node.js and Python 3 on PATH, from this directory:

```sh
python3 tools/check.py
```

`PHP_BIN` and `NODE_BIN` can point to alternate executable paths. No npm install is required for ordinary development. The PHP tests use a temporary isolated cache and delete it when done; they do not query a real league. JavaScript checks use Node's parser and do not execute the UI.

For integration checks, start `php -S 127.0.0.1:9393`, open the UI and test health/state/league/awards/fixtures plus a real revealed squad. Check startup errors, empty/preseason states and a failed upstream request. Repeat in a subfolder, since all app assets/API paths must remain relative. Check desktop and narrow viewports; these are retro desktop interfaces, so a scaled mobile view has readability limits.

Selector integration: test both entry points, numeric IDs and official H2H links, rejection of joining codes/Classic/Draft links, unavailable leagues without losing the previous choice, persistence after reload, independent browser contexts, Site default, Escape/cancel, and narrow viewports. Confirm contract typing does not leak into the manager name while the dialog is open. Verify a different request league clears host-specific preferences and isolates cache; no request must write global configuration.

## Package and publish

1. Run the checks and browser/integration checks. Review ASSETS.md and VALIDATION.md.
2. Update VERSION, CHANGELOG and validation notes. If the cache schema changes, update the cache namespace in runtime.php. Never overwrite a published version.
3. Run `python3 tools/package.py`. It checks first, packages only allowlisted source/assets, creates `releases/championship-manager-vVERSION.zip`, and writes its SHA-256 alongside it. It fails if that version already exists.
4. Inspect the ZIP listing and scan for private IDs, manager names, config.local.json, snapshots, logs, caches and backups. Generated archives belong in release assets, not the source tree.
5. Initialize a repository in this directory only, review the staged file list/diff, and commit. Do not initialize the parent audit folder or copy the original OneDrive folder wholesale.
6. Create/push the repository and a version tag only when publication is requested. Attach the matching ZIP and checksum to the GitHub release. Future work uses commits and tags, not renamed v18/v19 source copies.

The release allowlist in tools/package.py is authoritative for packaging. Add intentional new public files there and update this source map; never broaden it to the entire folder just to include one file.

## Classic interface checks

Check manager and Game Options menus, View/Sort/Filter using mouse and keyboard, Escape and focus return, plus popup bounds at 390px. Verify News selects today's oldest unread, retains the current story during refresh, scrolls using wheel/arrows/track/drag/keyboard, and exposes the full article body. Check real squad value/bank units and missing financial values, full league name with rank, and separators for small leagues. Refresh fixtures and awards without changing background, selected gameweek or month. Personal arrows must survive navigation/reload, remain separated by league/season/team, and never affect real FPL lineups. Clicking a disc opens its profile; dragging draws an arrow and must not open the profile. Test eight-direction snapping, length, touch/pointer cancellation, Delete and keyboard directions. Confirm tactics metadata contains only GK/DEF/MID/FWD, not availability codes or percentages. Inspect every screen and player/competition subtab, media sources, awards last-row scrolling, finance groups and profile footer proximity. Preserve the disabled Action Zones behavior.

Optional browser regression: with Playwright installed in your development environment and the preview running, set `CM_PREVIEW_URL` to the app's subfolder URL, `PLAYWRIGHT_MODULE` to the Playwright module path if it is not installed normally, and optionally `CHROME_PATH`; run `node tests/browser.cjs`. It uses real public-league responses for screen checks; if the current squad is not public it requests a real revealed finished-gameweek squad inside that isolated browser to exercise the layouts. Synthetic news is also confined to that browser. Test screenshots stay in a temporary directory outside source/releases.

For 0.5.0, also check Media unread persistence/Back and drag scrolling, mouse-versus-keyboard focus, shared thin selected tabs, Continue refresh feedback under a delayed request, repeated position labels, arrow maximum length (including stored vectors), Find pagination and club roster/back navigation.

For 0.6.0, compare the dark News/Media boxes, edge-aligned Next Unread, conditional scrollbar, setup sidebar and touching setup cells against the supplied references. Check hover invariance, divider centring in the scaled frame, named monthly/season-final History records, full transfer names and whole initial rows, sidebar-only Continue feedback, unread-alert click/hide behavior and no previous-page flash during slow Quick Start. Synthetic award/name/fixture cases belong in isolated tests only.

For 0.7.0, test settings and manager-selection arrows in both directions with real clicks (not forced clicks), Add Manager, no setup-title waiting text, exactly one read story on fresh startup and Continue, current-day selection after all current-day stories are read, and one-time repair preserving older flags. Delay club data and verify the previous view remains visible; cancel navigation and verify its late response does not reopen the old destination. Check compact awards, full-name search and absence of duplicated match-column titles.

For 0.8.0, verify constant View/Sort By/Filter captions, black dropdown arrows and plain checked menu options; passive sidebar cursor; two-second news blink; yellow selected edges (including hover) across tab families; omitted Referee Stats/Training/club History; readable monthly standings with whole-row scrolling.

Run `node tests/news-startup.cjs` with the same CM_PREVIEW_URL, PLAYWRIGHT_MODULE and CHROME_PATH settings as the main browser check. It isolates a NZ browser / UK game-date reproduction, legacy read flags across daily editions, exact oldest-today startup after all stories are read, and alert/photo behavior.

Current Continue behavior: quiet refresh with an unchanged label and no progress line; duplicate-click protection remains. Check that the sidebar alert clears at the top despite older unread backlog and reappears for genuinely newer unread stories. Only explicit Next Unread wraps backwards.
