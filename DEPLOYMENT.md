# PHP / Plesk deployment

Use a maintained PHP 8.3+ version with cURL and SimpleXML, outbound HTTPS access and writable private cache storage. The game requires no Node service or database.

1. Create a document root or subfolder for the game. Back up the current installation first.
2. Upload `index.html`, `api.php`, `runtime.php`, `classic.css`, `classic-ui.js`, `ui-rules.js`, `league-selector.js`, `.htaccess`, `splash.png`, `assets/` and `images/` from the extracted release. Keep the licence and asset notices with distributed copies. Tests/tools are source-maintenance files and need not be publicly deployed.
3. Put your configuration outside the document root, and set `FPL_CONFIG_FILE` for the site's PHP handler. Set `FPL_CACHE_DIR` outside the document root if you need durable cache. Alternatively, a demo installation needs no private config.
4. On Apache, the supplied `.htaccess` disables directory listing and blocks config JSON/runtime.php requests. On Nginx or IIS, configure equivalent deny rules; `.htaccess` is not processed there. Never assume a Git ignore rule blocks web access. Do not place private configuration in the web root when using PHP's development server.
5. Visit `api.php?route=health` and confirm the intended league ID and cURL availability. Then open `api.php?route=league`, and check fixtures and a manager squad through the UI.
6. Verify any in-web-root `config.local.json` is denied by HTTP. Prefer an outside-root config so there is no private file to serve in the first place.
7. Test visitor league selection and Site default from independent browsers; confirm a choice does not change the host default or another visitor.
8. Test the deployed root/subfolder URLs, browser assets and HTTPS. Do not copy old `data/` snapshots or old cache directories into the new installation.

The hosted example uses its own server-side configuration. A new installation uses the example league until you choose a league in the game or configure a host default. Use a PHP host to run the game.
