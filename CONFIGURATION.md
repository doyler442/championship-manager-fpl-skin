# Choose your league

Without a local override, the game uses real publicly readable H2H league 86140. Classic/Overall and Draft leagues are not supported by these backends.

## Visitor choice

Open **Game Settings** on the setup menu, or **Change league** on the manager-selection screen. During a game, use the Options button to return to setup. Enter a numeric H2H league ID or paste its official FPL H2H league link, then choose **Use league**. The app checks anonymous H2H access before saving. A joining code, Classic league or Draft league will not work.

The choice is remembered in this browser for this game and installation path. It does not change other visitors or server settings. Changing league reloads the game and returns to manager selection/name entry; previous league screens and in-memory data are discarded. **Site default** removes the saved choice after checking the configured league. Clear site storage to forget a choice, or use another browser/device to make an independent choice. No FPL login is required.

Only leagues readable through anonymous FPL access can be selected. An unavailable league leaves the existing choice unchanged. If a previously saved league later disappears, reopen the selector and choose another league or Site default.

## Host default (optional)

These settings choose the initial/default league for visitors who have no browser override. They are not needed merely to view your own league.

### Simple local setup

Copy `config.example.json` to `config.local.json`, keeping valid JSON. Change `leagueId` to the numeric ID in your league's FPL URL. For example, the number between `/leagues/` and `/matches/h` or `/standings/h` is the ID. A six-character joining code is not the numeric ID.

Set `userEntryId` to your numeric FPL team ID (from your team/points URL), or leave it at 0 to select a manager in the UI. CM selects a manager through its picker and remembers the choice; Keegan matches the entered manager/team name or chooses a manager view. These are views, not authentication. Setting a default manager does not restrict visitors from selecting others.

`config.local.json` is ignored by Git and excluded by the release packager. Never commit it. The app does not need your FPL password, cookies or access tokens.

## Production setup

Prefer placing your config JSON outside the web document root and setting the PHP environment variable `FPL_CONFIG_FILE` to its absolute path. This avoids relying on web-server rules to hide it. Set `FPL_CACHE_DIR` to a private directory outside the web root, writable by PHP. If omitted, cache uses a private per-install directory under the system temp directory; the host may clear it.

A local example (replace the sample path with your own):

```sh
FPL_CONFIG_FILE=/absolute/private/fpl-config.json php -S 127.0.0.1:9393
```

If both exist, `FPL_CONFIG_FILE` takes precedence over `config.local.json`. A missing/unreadable explicit file or invalid local JSON causes an error; it does not silently load the public league.

## Fields

| Field | Meaning |
| --- | --- |
| leagueId | Positive integer identifying a readable H2H league |
| userEntryId | Preferred entry ID, or 0 for selection |
| season | Display/cache season in YYYY/YY form |
| timezone | IANA timezone, e.g. Europe/Dublin or Pacific/Auckland; also determines monthly awards |
| pollMs | Refresh setting, minimum 30000 milliseconds |
| coinTossWinners | JSON object mapping month YYYY-MM to winning entry ID, only for a final tied award |
| staleMaxSeconds | Maximum total cache age allowed during upstream failure; 86400 by default, 0 disables stale use, maximum 604800 |
| maxLeaguePages | Pagination safety limit, 100 by default, maximum 1000; increase only for a genuinely larger league |

Timezone changes can move gameweeks between monthly awards. Choose it before the competition starts and document any later rule change. Neither a season label nor a league ID switch retrieves previous-season data automatically.

After configuration changes, reload the page and check `api.php?route=health`, then `api.php?route=league`. If the old settings persist on PHP-FPM, reload that site's PHP handler/opcache through the host. The full browser page reload clears the in-memory frontend cache.

## Request behavior for developers

`league-selector.js` adds the browser choice as the validated numeric `league` query parameter to every app API request. `api.php?route=league-check&league=86140` checks H2H standings and returns the league ID/name. Invalid IDs return HTTP 400; an unreadable/wrong-type league returns HTTP 422 on the check route. Requests without `league` use the host default. The server never writes this choice to configuration. Cache keys include the effective league and season. When it differs from the configured league, the configured userEntryId and coinTossWinners are cleared for that request; visitors cannot configure coin tosses. Timezone/season and cache policy remain host settings.
