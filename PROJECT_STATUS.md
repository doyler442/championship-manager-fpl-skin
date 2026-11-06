# Championship Manager project status

Updated 7 November 2026. This is the working checklist for this game. Update it after meaningful fixes, reviews and publication steps.

## Current position

Version 0.8.1 runs on the FPL site. The previous hosted build is available separately as v0.0.1. The current game uses server-side league settings, and visitors can choose another readable H2H league in Game Settings.

- [Hosted v0.8.1](https://fpl.cathaldoyle.com/championship-manager/)
- [Archived v0.0.1](https://fpl.cathaldoyle.com/championship-manager-v0.0.1.html)
- [MeTube development entry](https://dev.cathaldoyle.com/Open_Artefact:_Fantasy_Championship_Manager)

## Completed locally

- [x] Standalone PHP app with real public FPL H2H data, visitor league selection and browser-specific manager selection.
- [x] League tables, fixtures/results, squads, player profiles, transfers, monthly awards and competition history.
- [x] News and Media readers, persistent read state, scrolling, categories and Next Unread.
- [x] Retro layout, backgrounds, font, arrow/hand pointers, green menus and selected-tab edges revised against supplied screenshots.
- [x] Constant View / Sort By / Filter captions, black dropdown arrows and plain checked menu options.
- [x] Removed unwanted manager-selector selection fill and manager-directory hover fill.
- [x] Separate Team and Manager headings aligned over their respective monthly standings columns.
- [x] Red You Have News alert with one second visible / one second hidden timing.
- [x] You Have News and Continue Game advance one unread item per press; moving through news preserves the photo. Continue stops at the top without wrapping to older backlog, refreshes without replaying read stories, and reads genuinely new arrivals once each. The alert clears with the forward queue; older unread items remain accessible through the explicit Next Unread button. Automatic refreshes run quietly. A longer manual check shows white Please Wait text inside Continue Game.
- [x] Setup history arrows work; session startup waits for its destination instead of briefly showing the previous page.
- [x] Main asynchronous views prepare names, colours, data and photographs before revealing their destination; obsolete responses are ignored.
- [x] Startup chooses today's oldest unread story using the configured game timezone. If today's stories are all read, it opens today's oldest story.
- [x] Verified the remembered manager in the existing preview opens a 1 November row below the top of the list after reload.
- [x] Daily editions prevent a generated story redated to November from inheriting an October read flag. Existing legacy flags use their first-seen date where available; undated flags are retained when their date cannot be determined.
- [x] Local validation tools, automated checks, deployment/update guides, release allowlist and separately credited assets are in place.

## Verification

- The hosted PHP health check and the manager list loaded, and the server accepted an alternate public H2H league.
- The v0.8.1 Chrome regression covers startup selection, saved read state across a UK/NZ date boundary, newer unread news, and the white Please Wait label.
- The MeTube development page lists both playable builds and uses the current News screen for Latest.

The interface reads FPL data. Transfers and team changes still happen on the official FPL site.

## Where to look

- README.md — game behavior and local use.
- VALIDATION.md — checks performed and their limits.
- CONFIGURATION.md / DEPLOYMENT.md — league settings and hosting setup.
- CONTRIBUTING.md / AGENTS.md — implementation and assistant instructions.
- UPDATING.md / CHANGELOG.md — maintenance and release history.
- ASSETS.md / LICENSE — credits and licence boundaries.
