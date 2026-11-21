# Public read-only demo deployment

The public showcase is **not** the authenticated admin application. It uses only invented records from a reserved plant and serves GET requests; it never provides forms or API endpoints for editing them.

## Prerequisites

- Laravel 13 installed with valid production configuration and private `.env`.
- A working MySQL/MariaDB connection and completed migrations.
- Compiled Vite assets available in both the Laravel application's `public/build` and the subdomain's actual document root `build`.
- HTTPS and PHP 8.4 confirmed. The public subdomain must load Laravel through its existing protected `index.php`.
- Keep `APP_ENV=production`, `APP_DEBUG=false` and `LPA_PUBLIC_DEMO_ENABLED=false` until deployment checks pass.

## Upgrade from GitHub on Hostinger (SSH)

Back up the database and current `.env` first, and check Git status before pulling. Existing production data must not be replaced:

```bash
cd /home/u641948971/domains/devalejandre.com/apps/lpa
git status --short
git pull --ff-only origin main
/opt/alt/php84/usr/bin/php artisan migrate --force
/opt/alt/php84/usr/bin/php artisan optimize:clear
```

If your checkout has uncommitted changes, stop and reconcile them instead of forcing or resetting Git.

This update uses the **existing** Vite asset entrypoints and does not require a new front-end build when the current `manifest.json` is already present. If assets changed after a future update, compile them on a workstation and copy only the built assets.

## Create the isolated public scenario

Inspect the application database setting without sharing passwords or secrets. Once verified:

```bash
/opt/alt/php84/usr/bin/php artisan lpa:demo-seed --force
```

This command creates the reserved `DEMO-PUBLIC` plant and fictional users **without plant membership**, three questionnaires (levels 1–3), nine audits (six completed, three scheduled) and two actions with fictional findings. The seed does **not** delete existing audits, users, plants, or production records. It does not touch `DEMO-LPA`, the plant used for the admin account. Running it again will not duplicate the demo audits.

## Enable the public showcase

Add to the application's private `.env`:

```dotenv
LPA_PUBLIC_DEMO_ENABLED=true
```

Then refresh config:

```bash
/opt/alt/php84/usr/bin/php artisan optimize:clear
```

Open `https://lpa.devalejandre.com/demo` in an **incognito window**. Confirm filters work, a completed audit opens at `/demo/audits/{id}`, and the fictional corrective actions appear. No login or shared password is required.

Confirm also:
1. `/audits` redirects unauthenticated visitors to `/login`.
2. Unauthorized POST endpoints continue to reject unauthenticated requests.
3. A real/private audit ID cannot be opened through `/demo/audits/{id}` (404).
4. `https://devalejandre.com/lpa/demo` is blocked by the existing domain host rule.
5. The primary `https://devalejandre.com` site still works unchanged.

`X-Robots-Tag: noindex, nofollow` and `Cache-Control: no-store, private` are set on the demo responses; these are *not* authorization mechanisms. Privacy relies on the reserved-plant filter and separation from authenticated routes.

## Emergency off switch

To disable the public demo immediately, change the private environment variable to `LPA_PUBLIC_DEMO_ENABLED=false` and clear the configuration cache. All `/demo` pages then respond with 404, while the admin application remains available.

## Scope and limitations

This is a public, read-only presentation of database-backed fictional workflows, **not** a multi-user interactive sandbox. It has no public edit actions or automatic reset because no public write access exists. Do not share the supervisor account or use customer data in the demonstration plant. Reassess PHP/SQL security, rate limits and tenancy before expanding public functionality.
