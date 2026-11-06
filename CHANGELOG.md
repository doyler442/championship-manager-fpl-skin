# Changelog

## 0.8.2 — 7 November 2026

- Use the Fantasy Championship Manager title throughout setup and the game. Use the system pointer outside the game frame.
- Make Restart Game reload the page and Exit Game open a classic-style confirmation.
- Keep the setup menu steady on hover and give Change League the same grey styling as the in-game controls.
- Use the in-game panel borders for league settings and credits. Link to GitHub from Game Credits.

## 0.8.1 — 6 November 2026

- Restore forward-only Continue Game and You Have News. Continue keeps the latest headline when a refresh brings no new stories.
- Show white Please Wait text inside Continue Game during a load.

## 0.8.0 — 1 November 2026

- Keep squad control captions constant, add black dropdown triangles and simplify green-menu options while retaining selection ticks.
- Use the arrow over empty sidebar space and match the recorded news blink with one second visible and one second hidden.
- Replace selected-tab edges with yellow throughout the app, including hover, rather than adding yellow inside an existing dark border.
- Remove unsupported Referee Stats, Training and club History tabs; retain actual navigation boundary states.
- Group monthly team/manager names, enlarge primary text to league sizing and balance statistic columns. Preserve news read state.

## 0.7.0 — 1 November 2026

- Fix double News focusing during startup, Continue and manager-menu navigation. Keep entry on today’s stories after today’s queue is exhausted. Restore today’s unread flags once per manager, preserving older flags and retaining the prior set locally.
- Wire setup back/forward arrows through credits, manager selection and league settings; expose and raise league-sidebar controls above the form. Restore Add Manager as an existing-manager selector. Remove setup-title waiting text.
- Prepare data, header styling and decoded backgrounds before revealing the main asynchronous views; fetch club squad/schedule together, prefetch on manager hover and reject obsolete navigation/News responses.
- Match the compact, evenly divided Search For field and show full Surname, First name results; retain club browsing and filters.
- Give monthly standings a compact league-style layout with separate Team/Manager columns and no zone lines; omit duplicated team headings above match player lists.
- Extend browser checks for precise initial selection/read counts, one-time repair, setup history, full-name search and delayed/cancelled manager navigation.

## 0.6.0 — 1 November 2026

- Replace the large refresh modal with a small Please Wait state and moving line inside Continue Game. Keep setup visible while a new or quick-started session loads, until the fresh News screen/photo are ready.
- Match the setup sidebar’s compact arrow geometry, recessed version cell and yellow Restart/Exit labels; adjoin flat setup menu cells and remove mouse hover highlighting. Remove outer game/sidebar rims.
- Darken shared content boxes and News/Media headline/article fields, remove pale list outlines, hide unneeded scrollbars and shrink/align Next Unread to the edge.
- Add a blinking, clickable red unread-news notice at the foot of the sidebar; update it from background News refresh as well as reading actions.
- Remove decorative separator dots, simplify Next Match metadata, put home/away teams and scores in football order and replace schedule H/A explanations with both team names.
- Name monthly awards and History records; include the existing final-season champion as League Winner only after season completion. Centre dashed table lines in rank-cell gaps.
- Refine the original white cursor SVGs and use full FPL player names in transfer views, wrapping names and fitting complete initial rows.
- Extend presentation and browser regressions; retain all earlier archives.

## 0.5.0 — 1 November 2026

- Share News and Media unread selection, saved state, Next Unread and custom scrolling; contain red selection within headlines and preserve News on return. Remove the stray Filter label.
- Correct manager-menu ordering, placing Transfers below the divider and removing extra search/table entries.
- Add original white arrow/hand cursors throughout the interface and a live-clock match affordance; suppress mouse focus rings while retaining keyboard focus.
- Apply fine selected-tab outlines to Find as well as other screens; remove the live next-match yellow box, inert View/season/Clear Squad controls, empty scoring-event filler and yellow squad-name hover. Use repeated GK/DEF/MID/FWD starting slots.
- Cap drawn and previously saved tactics arrows to a reference-based 13% pitch-height maximum, with all eight directions retained. Restore a visible translucent profile-position strip.
- Show immediate real-date, indeterminate refresh feedback for Continue Game and ignore repeat clicks.
- Rework Find as compact open lists with full pagination, dark search and real club/roster browsing.
- Expand browser regression coverage; preserve earlier numbered archives.

## 0.4.0 — 1 November 2026

- Correct the sidebar reference treatment: recess only the clock, raise navigation cells and remove yellow selection feedback. Group and compact the manager popup menu.
- Open the oldest unread story from today, scroll it into view and advance towards newer stories.
- Align every screen’s typography, heading strips, tab/footer edges and scrolling regions, including media, awards, finances, search, directories, transfers and match subviews. Media now shares News’s five-headline/article layout.
- Use full stops in player summaries; add a compact recent-gameweek table from existing profile data and place the position label immediately below the content.
- Keep tactics position labels separate from availability; complete the pitch boundary and markings; replace direction-menu drawing with click/drag discrimination, eight-direction snapping, variable-length arrows and keyboard alternatives. Remove the persistent annotation explanation.
- Fix awards grid overflow and group financial/team information into compact tables.
- Retain prior versioned release archives.

## 0.3.0 — 31 October 2026

- Add a recessed sidebar, compact arrows, thin selection outlines, green menus and consistent bundled Jost typography, an open approximation of the reference lettering.
- Refine squad, player-profile, table and tactics presentation; preserve existing stats and the full league name while adding rank. Add nonduplicated table separators after first/third and above last.
- Add manager-page and Game Options menus; keep league selection and manager changes accessible. Action Zones changes text colour only.
- Select today’s newest unread news, retain story state during refresh, implement news scrollbar/wheel/keyboard controls and allow long article scrolling.
- Display gameweek-labelled FPL squad value, bank balance, combined value and transfer count/cost when available.
- Remove duplicate competition background selection and preserve photograph/tab/gameweek during refresh.
- Add optional eight-direction browser annotations on the tactics pitch, scoped to installation/season/league/team, with visibility, removal and clear controls.
- Add focused presentation-rule checks and an optional browser regression. Previous archives remain unchanged.

## 0.2.2 — 30 October 2026

- Moved league settings inside the actual game frame so it inherits the same scaling and position as other screens. Removed the separately scaled top-layer dialog that could shift and clip when focused.
- Preserved keyboard focus containment, Escape/cancel and underlying screen state.


## 0.2.1 — 30 October 2026

- Restyled league settings as a full CM screen with the existing photo background, red header, blue panel, yellow heading and grey buttons.
- Aligned league settings and credits text frames with the main header; removed the credits heading that overlapped the text panel.
- Preserved league validation, browser storage, cancellation and keyboard behavior.

## 0.2.0 — 30 October 2026

- Added a visitor H2H league selector accepting a numeric ID or official league link, with validation before saving and a Site default reset.
- Open **Game Settings** on the setup menu, or **Change league** on the manager-selection screen. During a game, use the Options button to return to setup.
- Browser choices apply to every API request, remain separate from host settings, and reload the game to clear previous league state. Invalid/unavailable leagues preserve the previous choice.
- Added request validation and regression coverage; updated human and AI maintenance/deployment guides.

## 0.1.3 — 30 October 2026

- Remove the visible Skip intro button.
- Hide the game-frame outline/shadow during the splash and make the surrounding viewport black.
- Restore normal game styling afterwards; retain automatic continuation and invisible click/tap/keyboard skipping.

## 0.1.2 — 30 October 2026

- Show the cleaned Daladad Software logo on startup, without the football or baked-in website address.
- Continue automatically after 2.2 seconds; allow mouse, Skip intro, Enter, Space and Escape to skip.
- Fetch league data in parallel; prevent menu interaction beneath the splash and preserve the existing loading/error states.

## 0.1.1 — 30 October 2026

- Disable manager selection until real league data has loaded; show an unavailable state after failed startup instead of opening an empty picker.
- Local preparation; v0.1.0 archives retained as earlier preparation snapshots.

## 0.1.0 — 30 October 2026

Prepared from CM v17 HTML/PHP.

- Default to real publicly readable H2H league 86140, with private configuration overrides.
- Remove original league identifiers, manager defaults and fallback snapshots from the public source.
- Standardize entry files and use relative asset/API URLs for subfolder hosting.
- Isolate caches by installation, league and season, outside the source/web directory; label bounded stale data and fail explicitly without usable data.
- Validate settings; paginate standings and fixtures without silently truncating them.
- Add human installation/update/contribution guides, AI instructions, focused regression checks and allowlisted ZIP packaging.
- Fix splash filename; exclude four unverified backgrounds and reuse credited photographs; correct swapped photo credits; derive points against/difference from completed fixtures instead of trusting placeholder zero totals.

Local preparation only. Originals are preserved; no production deployment or GitHub publication is implied by this entry.
