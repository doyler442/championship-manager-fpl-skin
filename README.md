# Fantasy Championship Manager

A retro football-management interface for real Fantasy Premier League head-to-head data. Version 0.8.1, prepared 6 November 2026. An independent fan project by Cathal Doyle, developed with ChatGPT/Codex; not affiliated with the Premier League, Sports Interactive or SEGA.

The default installation reads [H2H 2026/27, league 86140](https://fantasy.premierleague.com/en/leagues/86140/matches/h). This is a publicly readable third-party league, not a competition we operate. No FPL login is needed for the tested data. Availability can change, and league IDs may change each season. No manager snapshot, original private league configuration or fabricated demo results are bundled.

## Play online

Open the hosted game to browse the site’s league. To see your own Fantasy Premier League head-to-head league, open **Game Settings** and enter its league ID or FPL link. [Play Fantasy Championship Manager](https://fpl.cathaldoyle.com/championship-manager/)

## Run locally

Download and extract the release ZIP from [GitHub Releases](https://github.com/doyler442/championship-manager-fpl-skin/releases). With PHP 8.3+ and the cURL and SimpleXML extensions installed, run this from the extracted folder.

```sh
php -S 127.0.0.1:9393
```

Open the game in your browser. [Local game](http://127.0.0.1:9393/)

If the game cannot load data, check the API health page. [Local API health](http://127.0.0.1:9393/api.php?route=health)

PHP's built-in server is for local development only. Node is needed only for optional JavaScript checks, not to host the game.

To use your own H2H league, read [CONFIGURATION.md](CONFIGURATION.md). To deploy on Plesk, read [DEPLOYMENT.md](DEPLOYMENT.md). GitHub holds the source and downloadable ZIPs; GitHub Pages cannot execute the PHP API.

The cleaned Daladad Software logo appears for 2.2 seconds at startup while league data loads. The intro shows only the logo on a black background, without a button or game-frame border. Click/tap anywhere on it or press Enter, Space or Escape to continue immediately. The opening menu retains its Web Sites link.

## Choose a league in the game

Open **Game Settings** on the setup menu, or **Change league** on the manager-selection screen. During a game, use the Options button to return to setup. Enter a numeric H2H league ID or paste its official FPL H2H league link, then choose **Use league**. The app checks anonymous H2H access before saving. A joining code, Classic league or Draft league will not work.

The league settings screen uses the same photo background, red header, blue text frame and grey buttons as the other CM setup screens. Its text frame and the credits frame align with the main header.

The choice is remembered in this browser for this game and installation path. It does not change other visitors or server settings. Changing league reloads the game and returns to manager selection/name entry; previous league screens and in-memory data are discarded. **Site default** removes the saved choice after checking the configured league. Clear site storage to forget a choice, or use another browser/device to make an independent choice. No FPL login is required.

## What the interface does

Browse league standings, fixtures/results, revealed squads, player information and monthly awards. The CM interface also includes manager news and transfer views. These are read-only views: changing the displayed manager does not sign in, manage their team or make FPL transfers. Generated news uses FPL data and includes playful commentary; it is not official reporting. Some original menu items remain visibly disabled.

Monthly awards use finished gameweeks, 3 points for a win and 1 for a draw, then monthly FPL points. A remaining final tie requires a recorded coin toss. This is the application's award rule, not a claim about official FPL monthly prizes.

## Data and outages

Live responses are cached server-side outside this source directory. League and season are part of cache keys. During an outage, existing cache may be used for up to 24 hours and is labelled as previous/stale data; without a usable cache the app reports unavailability. It never substitutes the author's league or fictional managers. Provider news feeds are optional and may be unavailable.

## Maintain or extend

- [UPDATING.md](UPDATING.md): automatic data refresh, installing updates, season changes and rollback.
- [CONTRIBUTING.md](CONTRIBUTING.md): source map, tests and release workflow.
- [AGENTS.md](AGENTS.md): practical instructions for GenAI coding assistants.
- [CHANGELOG.md](CHANGELOG.md): changes from the supplied prototype.
- [ASSETS.md](ASSETS.md): artwork and separate asset notices.
- [VALIDATION.md](VALIDATION.md): tested scope and remaining limits.
- [PROJECT_STATUS.md](PROJECT_STATUS.md): hosted versions and verification status.

Source code is supplied under the MIT licence in [LICENSE](LICENSE). Third-party photographs, font and branding retain their own terms; see ASSETS.md.

## Classic interface update — 0.8.0

The blue sidebar with its recessed clock and raised navigation buttons, fine yellow selection lines, compact navigation arrows, green popup menus, squad lists, profiles, striped tactics pitch and league table share a locally bundled Jost font. Jost is a redistributable approximation of the reference lettering, not a claim to reproduce the original game font. An original white arrow is used on passive content throughout the game; links and working controls use an original white hand approximation. Pointer interactions do not add browser focus rings; keyboard focus remains visible.

The manager menu groups Squad, Next Match, Fixtures, Finances & Info and Tactics above a divider, with News and Transfers below. Search and League Table remain available through the sidebar rather than extra manager-menu entries. Game Options contains Change Manager, Change League and Setup Menu. View, Sort By and Filter use green menus: arrow keys/Home/End navigate, Enter selects, Escape cancels. Action Zones retains its original disabled behavior; only its text colour changes.

Entering News selects the oldest unread story dated today in the configured timezone. When today’s stories are all read, it opens today’s oldest story rather than the last-read/top story or an older date; older unread stories remain reachable through Next Unread. If there are no stories dated today, it can select an older unread story. Reading state is separated by installation path, season, league and manager. Read flags also record the story’s calendar-date edition, so a generated story redated on a later day does not inherit yesterday’s read flag. The configured game timezone controls both displayed dates and edition checks, independent of the visitor’s device timezone. Next Unread advances towards newer stories in the newest-first list. Background refresh preserves the current story. News supports wheel/trackpad, scrollbar arrows, track clicks, thumb dragging and keyboard scrolling; long article bodies scroll inside their panel.

Finances & Info includes FPL squad value, money in the bank, their combined total, gameweek transfers and transfer points cost, when supplied. These figures are labelled with the revealed gameweek and unavailable fields show a dash. The club competition link retains the league name and adds the team's H2H rank. Table separators appear after first and third and above last, without duplicate lines in small leagues; they do not assert that a public league offers prizes.

Photographs change on deliberate screen/competition-tab navigation. Data refresh, filtering and sorting preserve the photograph and selected competition tab/gameweek.

In Tactics, click a player disc to open the profile, or drag from it to draw a white arrow. Directions snap to eight compass points; dragging controls the length up to 13% of the pitch height, measured in screen distance for every direction, and endpoints stay inside the pitch. This cap approximates the supplied reference rather than claiming the original game’s algorithm. Existing longer saved arrows are also capped when rendered. Edit offers Draw arrows, Remove arrow and Clear arrows. View toggles visibility. Shift + arrow keys and numpad directions draw from a focused player; Delete removes its arrow. These browser annotations are stored separately by installation, season, league and team. Cancel/OK return from the tactics screen; arrows save when released. The tactics list always shows FPL positions; availability remains in player profiles.

All screens use the same header edges, purple tabs, uninterrupted translucent section-heading strips, open tables, compact typography and scrolling row areas. Media shares News’s five-headline/article layout, Next Unread, saved read state and wheel/track/thumb/keyboard scrolling. Returning from Media preserves the News story, filter and scroll position. Red selection is confined to the headline cell. Player summaries use full stops, with recent gameweek statistics and a translucent position strip directly below. Finances & Info groups team details and finances into compact columns. Awards reserve separate heading, gameweek and scrollable-table rows so lower standings remain reachable.

Continue Game opens one newer unread story per press and never wraps to older unread backlog. At the top it checks for new stories, preserving the current article when nothing new arrives. Newly arrived stories open in order, once each. Automatic refreshes run quietly. A manual check that takes time shows white **Please Wait** text in the **Continue Game** button. Repeat presses are ignored until the request finishes. It does not cover or dim the screen or simulate advancing the FPL calendar. The flashing live-match clock opens the match by click or keyboard and uses the hand cursor. Empty match-event columns omit filler messages.

Find has working Club and Player tabs, a dark search field, paging controls and compact open lists. Club browsing opens real FPL club rosters; player rows open profiles and club names open that club’s roster. Club, position and availability filters are retained for player lists. Unimplemented Nation/Non-Player search tabs, inert View selectors and dummy Clear Squad/season buttons are omitted. Other original unavailable features, including Action Zones, retain their existing behavior.

The setup sidebar uses the same compact arrows as the game, a recessed version cell and yellow Restart/Exit text. Setup menu cells adjoin with flat blue edges and no mouse hover fill/highlight. Starting or Quick Starting a session keeps setup visible until fresh News and its photograph are ready, preventing a glimpse of the previous game page. No Please Wait text is added to the setup title.

News and Media use dark translucent list/headline/article panels without pale outer rims. Scrollbars appear only when there are more than five rows. Next Unread is a small 124×19 button aligned with the right edge. A red “You Have News” alert blinks at the foot of the sidebar while real stories remain unread; clicking it advances through the unread sequence, keeping the current photograph. You Have News follows the same forward unread queue as Continue and disappears when that queue is finished. Older unread items remain available through the explicit Next Unread button. Background News polling can update the alert while another game page is open.

Presentation separator dots are removed throughout the interface and league selector. Next Match shows the two teams in home/away football order, then the gameweek and live status; schedules likewise show both teams instead of H/A labels. Player history groups its numbers with spacing. Transfer views use FPL first and last names, wrap long names and fit the initial viewport to complete rows.

Competition History labels monthly records “Manager of the Month” and adds a “League Winner” record only when the API marks the season final and supplies its champion. The monthly award screen also states the award name. Dashed table dividers are centred in the gaps between rank cells.

## Navigation and unread repair — 0.7.0

Startup and the manager menu focus News once, so only the opened story is marked read. The prior double-focus bug could skip a story and mark it read. This update restores the current day’s queue once per manager, keeps older read flags, and retains the previous read set in a browser-local repair record. Reading state and the repair are isolated by installation path, season, league and manager. The red sidebar alert is visible and blinks while unread stories remain, and opens News when clicked.

Setup’s third cell is Add Manager: it opens the existing FPL manager selector, without creating an FPL account or team. Setup arrows support back/forward history through setup, credits, manager selection and league settings; arrows with no destination are dimmed. The league panel’s sidebar remains clickable above its form.

Main manager/club, player, tactics, match, search, Media and transfer views prepare their data and decoded photograph before revealing the screen. Club squad/schedule requests run together, and hovering a directory manager can prefetch those records. Obsolete navigation and News responses are ignored after a newer choice. This avoids stale names, scores or header colours appearing briefly; it cannot eliminate external FPL response time.

Find uses two compact dark search fields of equal width and lists full player names as Surname, First name, following the reference. Monthly standings keep each team and manager together in one compact name column, with blue rank cells and evenly spaced statistics headed by grey cells, without table-zone divider lines. Match player-point columns omit team names already shown in the scoreboard.

## Reference detail corrections — 0.8.0

Squad controls retain the captions View, Sort By and Filter with small black dropdown triangles. Their green menus show plain option names and a tick beside the current choice. Empty sidebar space uses the arrow pointer. The red news alert follows the supplied recording: one second visible, one second hidden. Active purple tabs use a yellow border instead of an inset yellow line inside a dark edge. Unsupported Referee Stats, Training and club History tabs are omitted. Monthly standings group team/manager names together and use league-sized primary text with wider statistic columns. Existing read state and the one-time 0.7.0 repair marker are preserved.
