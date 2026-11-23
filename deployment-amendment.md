# Deployment amendment: clean URLs and public routes

This amendment corrects deployment setups where the site homepage loads but navigation exposes `/public` in URLs or sends those URLs to 404 pages.

## What changed

- `bootstrap/routes.php` now derives the mounted application path instead of assuming `/TimeHealersDigitalHub/public`.
- `appBasePath()` / `appUrl()` centralize URL generation for both a domain-root deployment and a project deployed in a subdirectory.
- Page navigation, forms, product/service links, account actions, and admin links now use that shared URL helper.
- The project-root `.htaccess` internally maps clean routes to `public/index.php`, leaving `/public` out of generated browser URLs. The router continues to accept legacy `/public/...` route requests where the hosting layout exposes them.
- The public `.htaccess` retains the front-controller rewrite when the host document root points directly to `public/`.

## cPanel `public_html` deployment

For a domain whose document root is `public_html`, use this layout. Do not upload the entire project inside `public_html`.

```text
/home/CPANEL_USER/
├── .env
├── app/
├── bootstrap/
├── config/
├── database/
├── resources/
├── routes/
├── storage/
├── vendor/
└── public_html/
    ├── .htaccess
    ├── index.php
    └── assets/
```

The private directories shown above are the project directories (including `public/`'s application entry files) except that the **contents** of the project's `public/` directory are copied into `public_html/`. The private project root must be the parent directory of `public_html`; this is why `public_html/index.php` can load `../bootstrap/app.php` using the existing `dirname(__DIR__)` bootstrap path.

1. Back up the deployed files and production `.env`.
2. Upload/copy `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `storage/`, `vendor/`, and root configuration files to `/home/CPANEL_USER/`, outside `public_html`.
3. Copy the **contents** of this project's `public/` directory (not the directory itself) into `/home/CPANEL_USER/public_html/`. This includes `index.php`, `.htaccess`, and `assets/`.
4. Confirm `/home/CPANEL_USER/public_html/index.php` is the project front controller. It loads `/home/CPANEL_USER/bootstrap/app.php` through `dirname(__DIR__)`.
5. Keep `.env` at `/home/CPANEL_USER/.env`, not in `public_html`. Set the production values:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://thdhubs.com
   APP_BASE_PATH=
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=YOUR_CPANEL_DATABASE
   DB_USERNAME=YOUR_CPANEL_DATABASE_USER
   DB_PASSWORD=YOUR_DATABASE_PASSWORD
   ```

   Leave `APP_BASE_PATH` empty when `thdhubs.com` is the domain root. Do not include `/public` in `APP_URL` or `APP_BASE_PATH`.
6. In cPanel, create the MySQL database and user, grant the needed privileges, then import the schema and seed files using phpMyAdmin or the hosting shell.
7. Ensure Apache `mod_rewrite` and `.htaccess` overrides are enabled. The `.htaccess` copied from `public/` sends clean route requests to `index.php`.
8. Set restrictive permissions on `.env` and private directories according to the host's guidance. `storage/private`, logs, backups, SQL files, and source code must remain outside `public_html`.
9. Clear any reverse-proxy/CDN cache after deployment.

If hosting restrictions make it impossible to store private code outside `public_html`, ask the hosting provider for a supported private directory or document-root configuration rather than exposing the entire project.

### Fix for a missing `bootstrap/database.php` fatal error

For the reported hosting path, the expected layout is:

```text
/volumes/bulk/thdhubsc/domains/thdhubs.com/
├── .env
├── app/
├── bootstrap/
│   ├── app.php
│   ├── database.php
│   └── routes.php
├── resources/
└── public_html/
    ├── index.php
    ├── .htaccess
    └── assets/
```

`public_html/index.php` loads `../bootstrap/app.php`. The bootstrap then loads `database.php` and `routes.php` from the **same `bootstrap/` directory**. Thus, the reported fatal error means `bootstrap/app.php` was found, but `bootstrap/database.php` was not deployed to:

```text
/volumes/bulk/thdhubsc/domains/thdhubs.com/bootstrap/database.php
```

Upload the complete local `bootstrap/` directory (at minimum `app.php`, `database.php`, and `routes.php`) to that exact server directory. Also upload `app/` and `resources/` beside `bootstrap/`; the application needs them at runtime. Preserve exact filename case because Linux hosting is case-sensitive. Do not create an empty `database.php` as a workaround. Keep `.env` at `/volumes/bulk/thdhubsc/domains/thdhubs.com/.env` and ensure it contains the real database host, database name, user, and password. Once uploaded, reload the site; a database configuration problem will then be handled by the application's database setup response rather than this missing-file fatal.

### Other supported layouts

- **Document root points directly to the project's `public/` directory:** deploy the project outside web root and configure the domain document root to that `public/` directory. Keep its `.htaccess` and set `APP_BASE_PATH=` (empty).
- **Project intentionally hosted in a URL subdirectory:** set `APP_BASE_PATH=/your/subdirectory` (never add `/public`), for example `/thdh` when the public URL is `https://example.com/thdh/`.
- The project-root `.htaccess` is for local/root-folder setups where the project directory itself is web-accessible; it is **not** the `.htaccess` to copy into `public_html` for the recommended cPanel layout.

## Smoke test

Open the site's root URL and check that:

- Main navigation points to `/services`, `/products`, `/blog`, `/about`, and `/contact` (with no `/public` prefix).
- No generated link contains `/public`.
- Product and service detail links load.
- The contact, login, registration, and newsletter forms post back to clean URLs.
- A non-existent URL returns the application's 404 page.
- The front controller returns the site's 404 page with HTTP status 404 for an unknown route.

If Apache returns 404 for all clean routes, verify `mod_rewrite`, `AllowOverride`, and that the web server is reading the deployed `.htaccess`. Do not work around the issue by adding `/public` to every navigation link.
