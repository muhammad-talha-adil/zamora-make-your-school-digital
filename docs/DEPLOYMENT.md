# Deploying to shared hosting (cPanel-style, subdomain)

For a hosting setup where the subdomain's document root is a `public_html`
folder and you keep the actual application code outside it in a sibling
`backend` folder — the standard, secure way to run Laravel on shared hosting
where you can't point the domain straight at a `public/` folder.

## 1. Two folders on the server

```
your-subdomain-root/
├── backend/          ← the whole Laravel app EXCEPT the public/ folder's contents
└── public_html/       ← ONLY the contents of Laravel's public/ folder, flattened here
```

- Upload everything from this repo (`app/`, `bootstrap/`, `config/`, `database/`,
  `resources/`, `routes/`, `storage/`, `vendor/`, `artisan`, `composer.json`, `.env`,
  etc.) into `backend/`. Do **not** upload the `public/` folder itself into `backend/`
  — its contents go to `public_html/` instead (next step).
- Copy everything that is currently *inside* `public/` (`index.php`, `build/`,
  `favicon.ico`, `robots.txt`, `.htaccess`, etc.) directly into `public_html/`
  — not the `public` folder itself, its *contents*.
- Delete the root-level `.htaccess` in this repo before uploading (it just
  redirects into a `public/` subfolder — irrelevant here, since `public_html`
  already *is* the document root). `public_html`'s own copy of `public/.htaccess`
  is the one that matters, and it needs no changes.

## 2. Edit `public_html/index.php`

The two paths that point at `../vendor/autoload.php` and `../bootstrap/app.php`
need one more `../` added, since `public_html` is now a **sibling** of `backend`,
not its child:

```diff
- require __DIR__.'/../vendor/autoload.php';
+ require __DIR__.'/../backend/vendor/autoload.php';

- $app = require_once __DIR__.'/../bootstrap/app.php';
+ $app = require_once __DIR__.'/../backend/bootstrap/app.php';
```

If `backend/bootstrap/app.php` or a maintenance-mode check reference other
`__DIR__.'/../...'` paths relative to `public/`, apply the same fix (search
`public_html/index.php` for every `__DIR__` reference after copying it over —
there are exactly two in a stock Laravel `index.php`, see above).

## 3. `.env` — everything below is a real blocker today

The current `.env` is a local dev file. Before going live, set:

| Key | Change to |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` — **critical**: `true` in production leaks stack traces, `.env` values, and file paths to any visitor who hits an error |
| `APP_URL` | your real domain, e.g. `https://school.yourdomain.com` |
| `APP_KEY` | generate a **fresh** one on the server: `php artisan key:generate --force` — don't reuse the dev key |
| `DB_HOST` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | your hosting provider's real MySQL credentials |
| `MAIL_MAILER` | a real driver (`smtp`, or your host's mail service) — currently `log`, meaning no email (password resets, notifications) is ever actually sent |
| `SUPPORT_CONTACT` | your real support email/phone — shown on the subscription-locked page |
| `ALLOW_ARTISAN_UI` | leave `false`/unset — this keeps the developer-only Artisan/Cache maintenance screens blocked outright in production |

## 4. Build/install steps to run once, on the server (or before upload)

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build          # produces public/build/ — already inside what you copy to public_html
php artisan migrate --force
php artisan db:seed --force       # only if this is a genuinely fresh install
php artisan storage:link          # if any uploaded files (school logo, staff documents) use the public disk
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`storage/`, `bootstrap/cache/`, and `backend/storage/framework/{cache,sessions,views}`
need to be writable by the web server user (`chmod -R 775` is usually enough on
shared hosting; some hosts require `755` plus correct ownership — check your
host's docs if you get a permission error).

## 5. Queue worker

`QUEUE_CONNECTION=database` needs something to actually process the queue.
Shared hosting rarely lets you run a long-lived `php artisan queue:work`
process — instead, set up a cron job (most cPanel hosts have a "Cron Jobs"
panel) running every minute:

```
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

and a second cron (or a supervisor-managed process if your host allows it)
periodically running `php artisan queue:work --stop-when-empty` — or switch
`QUEUE_CONNECTION` to `sync` if this app's queued jobs are rare/light enough
that running them inline is acceptable for now.

## 6. Before the first real visitor — do not skip

- Confirm the login page's dev-only "fill a test login" dropdown is gone
  (it was removed 2026-09-15 — if you're deploying from a branch predating
  that, delete it from `resources/js/pages/auth/Login.vue` first: it filled
  every seeded account's email/password into the login form from a public
  dropdown, which must never ship).
- Decide the real password for every seeded test account (`docs/TEST-LOGINS.md`)
  before going live, or delete those rows outright if this is a real school's
  data, not a demo.
- Set the school's actual `Subscription` row (`/settings/subscription`,
  developer-only) to whatever status is correct for launch — it defaults to
  `active` with no expiry, which is fine for a straightforward go-live.
