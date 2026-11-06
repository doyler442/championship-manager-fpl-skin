# Update guide for people and AI assistants

## Automatic FPL updates

The backend fetches FPL data when requested and caches it briefly: standings 60 seconds, selected-event fixtures 45 seconds, whole schedule/bootstrap 5 minutes, with shorter caches for live scores. The UI periodically refreshes core data. There is no daily data file to download or commit. A reload forces a new browser request but still respects backend cache TTLs.

News is optional. Failure of a news provider must not be interpreted as a match result. Stale data is previous real data, not a simulated replacement.

## Upgrade an installed release

1. Back up the installed code and your private configuration. Record your current VERSION. Keep private backups outside the web root and Git.
2. Download the next tagged source/release ZIP and review CHANGELOG.md and any migration notes.
3. Test the new version in a separate folder with the intended configuration. Compare `config.example.json` fields with your local settings; preserve your local values. New defaults are merged with your override at runtime.
4. Replace the application source/assets, including runtime.php, league-selector.js, classic.css, classic-ui.js, ui-rules.js, assets/ and server protection rules. Preserve the outside-root config/environment settings or ignored config.local.json. Do not overwrite a private config with the public example.
5. Reload PHP opcache if needed, then hard-refresh the browser. Check health, correct league, table, fixtures, manager selection, squads and awards. The packager never includes local config or cache.
6. If verification fails, restore the previous code/assets and its matching configuration backup, reload PHP, and retest. Keep previous numbered release ZIPs rather than overwriting them.

## Remembered visitor choices

Updating files preserves browser league choices. Use Site default in the selector when checking a changed host default. Browser overrides take priority; changing the server config does not overwrite them. Visitors must select their new H2H league when its ID changes for a new season. The host must also update the season/timezone configuration as appropriate.

## New season or different league

1. Find the new season's H2H league ID in its FPL URL; do not reuse an old ID without verifying it. H2H competitions may need recreating each season.
2. Update private `leagueId`, optional `userEntryId`, and `season`. Clear the old `coinTossWinners` object for a new season. Confirm the timezone and award rule.
3. Reload and inspect league name, entrants, fixtures and a revealed squad. Before the first deadline, squads/fixtures may legitimately be unavailable; show that state rather than filling it with results.
4. Cache keys already separate seasons and league IDs. Delete only this app's cache files if troubleshooting requires it; do not clear the entire system temp directory. A changed timezone or calculation rule may also justify clearing this app's cache.
5. If changing the public default, update runtime.php defaults, config.example.json, README.md and validation records together. Verify anonymous access, fixtures, pagination and squad access before releasing.

## Change the software

Follow CONTRIBUTING.md and AGENTS.md. Keep one source tree: public and personal installations differ through configuration. Use a branch for a focused change; describe its behavior and validation. Update docs when settings, routes or scoring behavior change, and record known limits honestly.

Example request to an AI assistant:

> Read AGENTS.md and UPDATING.md. Add [specific feature] while preserving current H2H scoring and visual style. Keep local configuration and downloaded data out of the change. Run the documented checks, explain the files changed and report any verification you could not complete. Do not deploy or publish unless asked.

## Classic UI maintenance — 0.3.0

Keep background changes tied to deliberate navigation. Competition polling calls `loadLeague({refresh:true})`, preserves tab/gameweek/scroll and must not request a new photograph. League entry chooses only the tab background; do not also choose a generic route photograph. `CM_BG_PROMISE` allows navigation to await image decoding.

Keep the native select values and change handlers as the source of truth for green menus. News read/last-story and tactics-arrow keys include path, season, league and entry; do not migrate private names or snapshots into source. Tactics arrows are local annotations, not generated tactical claims. Their coordinates follow player positions and remain clipped to the pitch. Asset licences travel with any release.

## Reference corrections — 0.4.0

Only the date/time cell is inset. Sidebar clicks do not add a yellow border or yellow text; retain cyan manager text and yellow Game Options text. Green manager menus group squad, match, fixtures, finances and tactics above News/Transfers; do not add search/table shortcuts here. Keep shared heading strips free from inset horizontal rules, and apply typography/spacing to every page and subtab.

News opens the oldest unread story from the current configured calendar day and Next Unread moves towards newer stories. Do not reinstate newest-unread selection or reset offset to zero after focusing a story. Tactics metadata is the player's position, independent of chance/status. Pitch linework is one complete SVG below the discs and arrows. Pointer down/move/up distinguishes click-to-profile from drag-to-draw; cancellation must preserve the previous stored arrow. Keep the persistent explanatory note empty. Avoid layering a second fixed-height content area under awards headings; use the remaining grid row for scrolling.

Saved direction arrows from v0.3.0 are read from the old v1 storage key and converted to percentage vectors when the new v2 key is absent. New drags write only v2; do not reinterpret short percentage vectors as old unit directions.

## Reader and interaction corrections — 0.5.0

News and Media share `readerScrollbar` in classic-ui.js. Media read/last-story keys are separated by path, season, league and manager just like News. Do not reset Media on every route restoration or disturb News on Back. Keep selection backgrounds inside headline cells and Next Unread below the list.

`CMRules.limitArrow` caps drag, keyboard and stored vectors at 13% of rendered pitch height; endpoints are still bounded. This is a visual approximation from the reference. Pointer focus rings are suppressed using input modality, without suppressing keyboard focus. Continue Game paints its real-date progress panel before awaiting data and guards repeat clicks. Find pagination covers all players; do not reinstate a fixed first-250 truncation. Club rosters use existing normalized FPL club fields.

## Reference details — 0.6.0

Keep Continue feedback within its sidebar cell; do not restore the large Updating News overlay. Starting a session keeps setup on top until News/photo decoding finishes. Setup and game arrows use the same 11×9 geometry; setup cells touch and have no hover highlight. News/Media list containers have no pale rim, dark individual headline fields and conditional scrollbars. Keep Next Unread aligned at right:10px with a 124×19 footprint.

Unread alerts use the existing path/season/league/manager read scope. Placeholder error/empty stories do not trigger the alert. Polling News on other game pages must remain quiet and preserve the active route/photo. `CMRules.awardHistory` names monthly/final records from existing API fields and must not announce a champion during an unfinished season. `fixtureSides` preserves football order and corresponding scores; `fullPlayerName` uses existing normalized name fields without changing API shapes. Avoid decorative middle-dot separators in presentation strings.

## Navigation and read-state repair — 0.7.0

Do not call `focusInitialNews` again after `loadNews({focus:true})`: that was the skipped-story/double-read bug. Open actions focus once; quiet background refreshes do not mark unseen stories. Entry stays on today after today’s unread stories are exhausted. `repairTodayReadQueue` restores only current-day IDs once per manager and saves the previous set in `cm-read-repair:v070:` browser storage. Do not clear the repair key during normal use.

`readyRoute` retains the previous view until data and the photograph are ready, then commits header/data/view together. `NAV_REQUEST` cancels stale responses and `NEWS_REQUEST` separates obsolete manager-news requests. Avoid revealing a reused view before applying its new manager identity and theme. Setup history is separate from game history; league close must finish before reopening/restoring another setup route so focus/inert state is restored correctly.
