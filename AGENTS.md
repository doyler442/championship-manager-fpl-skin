# Instructions for AI coding assistants

Read README.md, CONFIGURATION.md, UPDATING.md, CONTRIBUTING.md, ASSETS.md and VALIDATION.md before editing. This is a standalone PHP application; do not add a framework or dependency service for routine fixes. Preserve its retro presentation unless the user requests a design change.

## Work within scope

- Public demo and personal installations use the same source with different configuration. Never hard-code an author's league, manager ID or fallback name. Public default is a configurable publicly readable H2H league.
- Do not read/commit private local configuration, old snapshots, runtime caches, logs or deployment backups to infer example values. Use config.example.json for documentation. No FPL passwords/cookies/tokens are required.
- Do not substitute Classic/Overall or Draft endpoints for H2H endpoints. Preserve API response shapes used by the frontend; update both together if necessary.
- Match and award scores must come from real FPL responses. Synthetic data is confined to isolated automated tests. Do not populate the public demo with fictional managers or results.
- Monthly awards count finished gameweeks only, 3/1/0 points, then monthly FPL points, with a recorded coin toss for a remaining final tie. Incomplete months do not declare a final winner.
- Visitor league choices are request-local. Preserve numeric validation, anonymous H2H checks, browser isolation and full state reset on change. Never write visitor choices to global config; clear league-specific host preferences for a different league.
- Keep cache keys separated by league and season; preserve stale labels and age limits. Never silently truncate league/fixture pages. Missing data must be explicit.
- Keep URLs relative for subfolder hosting. Escape remote names/text before HTML rendering. Validate new request parameters and avoid accepting arbitrary proxy URLs.
- Config/cache belong outside the public document root in production. A gitignore is not an HTTP access control.
- Preserve third-party attribution and licences. Style-reference screenshots are not release assets. Do not claim the code licence covers third-party photos or branding.

## Validation and delivery

Run `python3 tools/check.py` from the repository directory; PHP_BIN/NODE_BIN may select installed interpreters. See CONTRIBUTING.md for local server and browser checks. Add focused tests for changed scoring or cache contracts. Report failed/unavailable verification rather than claiming success.

Update documentation with behavior, settings, routes or workflow changes. Use tools/package.py for ZIPs; inspect its output and staged files before publication. Do not include local config, caches, snapshots or private audit notes. Never rewrite a published version ZIP. Do not deploy, publish or contact league organizers without user authorization.

A sample task prompt is in UPDATING.md. AGENTS.md documents the project; it is not a guarantee that every AI tool automatically discovers this file. A human can point any assistant to it explicitly.
