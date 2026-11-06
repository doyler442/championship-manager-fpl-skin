# Validation — 2 November 2026

Local preparation, not a production deployment.

## Version 0.8.1 — 6 November 2026

- Restored the News queue from the pre-loader build. Continue and You Have News advance through newer unread articles. Continue keeps the latest headline after an unchanged refresh.
- The Continue Game loading state shows white Please Wait text. Automatic refreshes stay quiet.
- The Chrome startup regression verifies the alert appears for newer unread stories, an older unread story stays unread after Continue reaches the latest headline, and new arrivals open once each. It also checks that Please Wait is white and the original label returns after refresh. PHP, JavaScript and asset checks passed.

## Quiet refresh and shared news alert — 2 November 2026

- Continue keeps its label during refresh, without a waiting label or progress line. Duplicate requests are still prevented.
- The sidebar alert and Continue share the same forward unread lookup. Older unread items below the current article cannot keep the alert visible or cause its click to jump backwards. Explicit Next Unread can still access that backlog.
- Focused browser checks cover reaching the top with older unread stories remaining, a hidden alert that cannot reopen them, two new arrivals making the alert visible, and the alert clearing after the last new arrival is read. Repeated refreshes preserve the article and photo.


## Continue boundary correction — 2 November 2026

- Continue no longer wraps to unread stories below the current article. News alert / Next Unread retain their separate older-backlog behavior.
- Refreshing after reaching the top does not invoke startup selection. An unchanged response keeps the latest article; two newly arrived stories open oldest first and are each read once.
- The dedicated startup/Continue browser regression presses Continue through the top, two unchanged refreshes, two new arrivals and two further refreshes. It checks article identity, request counts, retained older-unread flags, four distinct read stories and unchanged background throughout.


## Daily News selection follow-up

- Generated stories can retain their ID while their publication timestamp moves to a later day. Read markers now retain calendar-date editions; legacy markers use the first-seen timestamp when available, preserving unambiguous old read flags without treating a newly dated edition as already read.
- A focused isolated Chrome check uses Pacific/Auckland for the browser and Europe/Dublin for the game. Saved October markers do not suppress November editions; startup opens the earliest unread November item, the next alert click opens the newer November item without changing the photo, and re-entry after both are read opens November's earliest item.
- The configured timezone is used for display, day selection and read editions. Pure checks cover the UK/NZ midnight difference. Required PHP/backend/presentation checks and the full browser sweep passed.
- Reloaded the existing preview and Quick Started the remembered manager. The selected row is dated Sun 1st Nov AM, at list offset 9, rather than the top item or a 31 October story. The article headline mentions the October award race; its publication row is dated 1 November.
- No timezone configuration or global unread-queue reset was applied.



## News queue navigation follow-up

- Chrome checks alternate You Have News and Continue Game across newer unread items and an older unread backlog. Each press reads exactly one story; reading the queue makes no manager-news request and preserves the photograph request counter, including entry from another page.
- Once the queue is cleared, Continue refreshes once, retains its sidebar waiting feedback and ignores repeat presses while refreshing. Incoming unread stories still open correctly. The full screen sweep and required PHP/JavaScript/asset checks passed.
- Startup's agreed today-first selection and existing read-state storage are unchanged. Empty/unavailable placeholders do not block Continue as unread news.

## Reference detail corrections — v0.8.0

- Measured the supplied 9.65-second recording: visible transitions at approximately 0.4, 2.4, 4.4, 6.4 and 8.4 seconds, with one-second visible/hidden intervals. Alert duration is two seconds with discrete visibility changes.
- Required PHP lint, 25 backend regressions, JavaScript, asset and allowlist checks passed. Desktop/narrow Chrome sweep passed with real public data; plain squad option selection, neutral captions, sidebar cursor, two-second animation, external yellow borders and absence of Referee Stats are checked explicitly.
- Inspected squad, green menus, league and award screenshots. Monthly primary names/statistics use the league's 12px font size, compact whole-row scrolling and balanced columns. Unsupported Training and club History are removed. Legitimate disabled paging/setup boundary states remain; Action Zones retains its previously requested behavior.
- The 0.7.0 read-state repair key is unchanged, so this visual update does not reset the queue again.


## Navigation and unread repair — v0.7.0

- Required PHP lint, 25 backend scoring/cache regressions, presentation rules, JavaScript syntax, public assets and allowlist checks passed. New rules cover today-only entry fallback, full surname/first-name formatting and restoring today without changing older read flags.
- Desktop Chrome screen sweep exercised all main pages and player/club/competition subtabs, Media, search/club rosters, tactics, match views, setup/settings/manager selection and narrow layouts. News startup marks one story instead of skipping and marking two; the initial selection is the earliest unread dated today.
- Targeted browser checks cover settings-arrow back/forward clicks, manager-selector back/forward, three setup cells, compact monthly rows, full-name search, current-day read repair, removed setup waiting text, delayed manager transitions and cancellation of obsolete navigation. An invisible full-screen settings form was found intercepting the sidebar; its sidebar controls now sit above that form.
- The existing in-app browser was reloaded on the remembered manager: today’s restored queue and the red unread alert were visibly verified. Prepared awards navigation retained a decoded, visible photograph. Existing real FPL API shapes, scoring/cache logic and read-only operation are preserved. One-time read repair affects browser storage only, retaining the prior set locally. Synthetic arrivals and artificial delays are confined to the isolated test browser; screenshots stay outside releases.
- Exact original font/cursor assets, every browser/device and production PHP/Plesk remain outside verified scope. Local preview and release checks do not constitute deployment.

## Screenshot detail corrections — v0.6.0

- PHP lint, 25 backend scoring/cache checks and presentation/JavaScript/asset checks passed. New isolated rules cover full names, home-first team/score ordering and named monthly/final-season award records; unfinished seasons cannot emit a League Winner.
- The full desktop Chrome screen sweep passed using real public FPL data. It covered setup/credits/settings/selection, News categories, manager and View menus, every club/player/competition subtab, tactics, Media outlets, directories, player/club search, transfers and match views, plus narrow menus/settings. Synthetic reader data and delayed refreshes remain confined to the test browser.
- Verified setup mouse hover does not change fill/text/shadow, setup arrows share the game’s 11px width, Restart is yellow, table divider centres account for frame scaling, short News lists hide the scrollbar, list border width is zero and Next Unread has the specified edge/footprint.
- Continue’s delayed refresh stays within its existing button, with no loader visible and no repeated request. The red unread alert opens News and disappears after all stories are read. A separate incoming-story check confirms a quiet refresh on another page retains its route/photo and leaves the story unread until clicked. Delayed Quick Start keeps setup visible until the fresh News route is ready; the previous page is not exposed.
- Visually compared updated News/Media boxes, setup sidebar/menu, profile/history, table and dividers, finance/tactics/match pages, compact refresh, named awards and full transfer names against the supplied screenshot references. API response shapes/scoring/cache logic are unchanged. Ordinary UI and league-selector strings contain no middle-dot separators.
- Native PHP/Plesk production, every browser/device and exact original cursor artwork remain unverified. Cursor vectors are original approximations. Previous versioned ZIPs are retained.

## Reader and interaction corrections — v0.5.0

- Required checker passed: PHP lint, 25 scoring/cache regressions, news/timezone/finance/table rules, bounded eight-direction arrow tests, JavaScript syntax, public assets and release allowlist.
- Expanded desktop Chrome sweep exercised every main screen and player/competition subtab, Media outlets, real club/player search, transfer views and match views, plus narrow menus/settings. Real public FPL responses were used for displayed league, squad, profile, club and scoring data. Test-only synthetic news/media and a delayed refresh were confined to the isolated browser.
- Verified the manager menu's exact group/order, repeated position slots, no player-name hover colour change, no pointer ring on pitch/scrollbar drags, maximum drawn/stored-vector length, keyboard arrow removal/drawing and profile opening. White arrow/hand cursor URLs and live-clock affordance were checked through computed styles.
- Media oldest-unread selection, Next Unread, wheel, Home, thumb dragging, headline-contained red selection and Back preserving News ID/filter/offset passed. News oldest-unread, advancing, wheel and Home/End checks still passed.
- Find pagination reaches beyond the former first-250 cutoff; Club opens real rosters and Back restores the club directory. Hidden inert View controls and Continue Game's immediately visible date/progress/repeat-click guard passed. Shared thin tab lines, full position band, compact search, Media and progress captures were reviewed.
- No app JavaScript exceptions or missing app assets occurred. Browser favicon requests return 404 and are outside the application asset check. Native PHP/Plesk production, other browser engines, physical devices and exact original cursor/arrow algorithms remain unverified. Cursor shapes and the 13% pitch-height arrow cap are original approximations from the supplied references.

## Reference correction pass — v0.4.0

- Visually compared desktop captures across setup, credits/settings/manager selection, News categories, manager/View menus, all club tabs, tactics, every player tab, table/results/fixtures/schedule, player stats/awards/history, Media sources, directory/search/transfer centre, and match overview/stats/report/lineups/latest. Shared heading edges, translucent strips, text weights and row/table spacing were checked against the user-supplied reference screenshots. Images remain local review material, outside release assets.
- Real anonymous public league data was used. During the Gameweek 9 deadline transition the default squad was temporarily unavailable: the explicit unrevealed state was checked, and a real revealed Gameweek 8 API response was used inside the isolated test browser to exercise squad/profile/tactics layout. No synthetic managers, squads or results were put into the app or its release.
- News entry selected a later story and adjusted offset (observed index 6 of 15, then index 9 of 16 and 18 as upstream stories changed). Isolated synthetic-news checks verify oldest unread today, Next Unread towards newer stories, scroll offset, wheel, Home/End and enabled Next navigation.
- Sidebar screenshots confirm only the date/time is recessed; navigation text remains white/cyan/yellow according to its normal role, with no added yellow click highlight. Manager popup grouping and keyboard/mouse cancellation were inspected.
- Pointer drag creates a saved arrow without opening a profile; a normal click/Enter opens the profile. Delete and Shift+arrow drawing passed. Tactics labels contain only GK/DEF/MID/FWD, the pitch has continuous boundary/box/centre markings, and the persistent annotation note is empty. Local v0.3.0 unit-direction arrows migrate to the new vector storage.
- Profile punctuation and footer proximity, finance-panel bounds, whole-row awards-table sizing/last-row scrolling, match tabs above the commentary strip, stable refresh photograph/tab/gameweek and 390px menu/settings bounds are covered by the expanded optional browser regression.
- Required PHP lint, 25 backend scoring/cache regressions, presentation-rule checks, JavaScript syntax, asset paths and release allowlist passed with the existing temporary PHP.wasm runtime. Exact original font/cursor-video coverage, native PHP/Plesk production, physical devices and every browser engine remain unverified; Jost remains the documented approximation.


## Classic interface — v0.3.0

- Required checker passed with the existing temporary PHP.wasm runtime: PHP lint, 25 scoring/cache regressions, new news/timezone/financial-unit/missing-data/table-separator checks, JavaScript syntax, HTML assets and release allowlist.
- Headless desktop Chrome exercised real public league 86140: ten managers, revealed 15-player squad, news, profile, table, fixtures, financial values from revealed Gameweek 8, manager/options menus and league-selector cancellation. No JavaScript exceptions were observed.
- Mouse selection and keyboard Enter/End selection in green View/Sort menus passed. Escape cancels; menu bounds passed at a 390px viewport. Desktop news, squad, tactics/arrow, profile and table screenshots and narrow menu screenshots were visually inspected.
- Background request/image and selected fixtures tab/gameweek remained unchanged during explicit competition refresh. Generic league-entry background selection was removed so only the competition tab chooses its image.
- Real-data pitch discs open profiles after finishing annotation; optional diagonal arrow creation passed. Synthetic news was injected only inside an isolated automated test browser to verify newest-unread selection, wheel, keyboard Home/End, thumb dragging and long-body overflow. It is not demo content or a bundled snapshot.
- Jost is a bundled open approximation; exact original font and full cursor coverage in the unavailable reference video are not claimed. The custom arrow applies only over passive profile content established by the supplied screenshots.
- Native PHP/Plesk production, physical devices, all browser engines and exhaustive external data conditions remain unverified. Existing hosting/data limitations below still apply.


## Game-frame scaling correction — v0.2.2

- Verified in the actual in-app browser used for the reported clipping. Settings is now a child of the game frame and inherits its scaling, with no separate viewport-position calculation or inner transform.
- Observed settings/game rectangles matched exactly (547×410.25 at the tested browser size); horizontal scroll remained zero after input focus and keyboard navigation. Header/panel edges remained aligned.
- Shift+Tab wraps to Cancel, Cancel restores the setup menu and its original focus, and reopening remains aligned. Captured the corrected screen for review.
- Backend and league selection contracts are unchanged; required static/regression checks run for this release. Keegan is unchanged.

## CM screen consistency — v0.2.1

- Desktop and 390px screenshots inspected for the full CM league screen, matching the existing photo, red header, blue panel, yellow heading and grey buttons.
- Browser geometry checks confirm league and credits panels share the exact left/right edges of the main header. Credits no longer has an overlapping duplicate heading.
- Resize, Escape, cancel/return to manager selection, real league validation, persistence, independent browsers, failure preservation and Site default passed without JavaScript errors.
- Required PHP/JavaScript/asset/allowlist checks and 25 regression checks passed. Keegan source and prior versioned archives were unchanged.

## Visitor league selector — v0.2.0

- PHP lint, JavaScript syntax, asset paths and release allowlist checks passed. Isolated regressions include malformed visitor parameters, league-specific preference reset, immutable deployment settings and existing cache/scoring contracts.
- Chrome checked both selector entry screens, real league 86140 validation, numeric/official URL parsing, reload persistence, independent browser contexts, invalid input, failed validation preserving the saved choice, Site default, Escape and cancel. Failure-path browser responses were intercepted only in the isolated test.
- Desktop and 390px dialog screenshots inspected. No JavaScript exceptions observed in these flows. Normal manager selection and live table/squad navigation passed. Both alternate selector entry points and Keegan physical-keyboard isolation/Enter submission passed. Request health checks confirmed another league does not change the deployment default; malformed API parameters were rejected.
- Hosted PHP/Plesk deployment and protection checks remain outstanding; local tests do not establish production behavior.

## Current splash — v0.1.3

- No visible Skip intro button or surrounding game-frame outline/shadow. The entire viewport background is black during the logo.
- Desktop (1280×900) and mobile (390×844) browser checks confirmed black background, no button and no frame; screenshots inspected.
- Automatic continuation, click/tap and Escape skipping passed. Normal game-frame styling returns after the intro; the real manager picker still works. No JavaScript errors observed.


## Previous startup splash — v0.1.2

- The browser serves the exact cleaned Daladad logo asset. Desktop and 390px screenshots inspected.
- Splash is visible immediately, hides after 2.2 seconds, and supports Skip intro/click, Enter, Space and Escape.
- Slow API responses do not delay the splash transition; the menu stays disabled until real league data is ready. Manager selection after the intro passed.
- When API requests fail, the splash still skips and the existing unavailable state appears. No JavaScript errors in these flows.


- PHP lint and isolated scoring/cache regressions: run with WordPress Playground PHP.wasm CLI (PHP 8.5.10). This temporary development runtime is not bundled or required in production. Native PHP/Plesk verification is still needed on the target host.
- Node.js syntax checks: inline CM JavaScript and Keegan app.js.
- Real anonymous FPL league 86140: health, state, ten-manager standings, monthly awards, and five Gameweek 8 fixtures successfully returned locally. Revealed 15-player squads, medical and scouting routes also passed through the updated applications. CM schedule, manager/league transfers, media and manager-news routes returned valid JSON.
- Automated desktop Chrome: public-league startup, table, CM news, Keegan office and fixtures; CM manager selection disabled until league data loads; no JavaScript exceptions or local asset HTTP errors in the captured flow.
- Actual mouse-click flows passed: CM new-game manager selection to news/table, and Keegan boot/contract to office/table/squad. No JavaScript errors in those flows.
- Desktop and 390px screenshots inspected. CM deliberately scales its 800x600 interface; text is small on phones. Physical-device, extended-session, all-player and every-menu testing are not claimed.
- Outage/cache tests cover league/season separation, bounded stale data, expiry, completed-only awards, draws, final ties, configured coin-toss winner and empty/preseason data. Synthetic fixtures exist only in tests.
- Private override health checks passed for both applications without querying the private league. Malformed configuration fails with a clean JSON error. A local privacy scan checked release source against original league/entry identifiers and team names.
- Private configuration is stored separately from both source folders. Release packaging uses an explicit file list, excludes settings/caches/snapshots, and creates immutable versioned ZIPs with SHA-256 checksums.
- All bundled CM photo source links/licences recorded in ASSETS.md/ASSETS.json; four unverified images and style screenshots excluded. Keegan's missing office art is replaced with an original SVG.

Not yet verified: production upload, Apache/Nginx deny rules on the target server, other browsers/hardware, future seasons, very large H2H leagues, every external news provider, or exhaustive gameplay. These are read-only league viewers; no FPL team-management actions are implemented.
