# Pick Me Retailers — Production Deployment

Deployment target: **Digital Ocean VPS** behind **Cloudflare** (Full strict SSL),
with **supervisor** running the queue worker and scheduler.

Payments are collected **manually via GCash** (a QR code / mobile number shown
at checkout) and confirmed by an admin from the dashboard. There is no
third-party payment gateway to apply for or approve, so the whole flow can go
live immediately. The GCash account details and QR image are configured after
deployment from **Admin → Settings → GCash Payment**, not through `.env`.

---

## 1. VPS baseline

- Ubuntu LTS (or Debian) server.
- Install: PHP `8.2` + extensions (`mysql`, `gd`, `curl`, `zip`, `mbstring`,
  `xml`, `bcmath`, `intl`), MySQL/MariaDB, nginx, composer, supervisor, git.
- Clone the repo to `/var/www/ecommerce`, ownership `www-data`.

## 2. Database

- Create a **dedicated least-privilege app user** (do not use `root`):

```sql
CREATE DATABASE ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'app_user'@'127.0.0.1' IDENTIFIED BY 'STRONG_APP_PASSWORD';
GRANT ALL PRIVILEGES ON ecommerce.* TO 'app_user'@'127.0.0.1';
FLUSH PRIVILEGES;
```

- Run migrations: `php artisan migrate --force`.

## 3. Rotate all previously-exposed secrets

The DB password (and other credentials) were exposed in git history. Treat
**all** of these as compromised and rotate them:

| Credential | Where to rotate | `.env` var(s) |
|---|---|---|
| DB password | MySQL (DB was previously leaked) | `DB_PASSWORD` |
| Gmail SMTP app password | Google Account > Security > App passwords | `MAIL_PASSWORD` |
| Google Maps key | Google Cloud Console (restrict to your domain) | `GOOGLE_MAPS_API_KEY` |
| Google OAuth secret | Google Cloud Console | `GOOGLE_CLIENT_SECRET` |

Generate a strong DB password with: `openssl rand -base64 24`.

## 4. `.env` (production, on the VPS only — never committed)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pickmeretailers.com          # real domain, NOT the ngrok tunnel

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_USERNAME=app_user
DB_PASSWORD=STRONG_APP_PASSWORD

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true                # required; HTTPS-only cookies
SESSION_ENCRYPT=true

LOG_CHANNEL=daily                          # rotate logs daily (avoid 17MB single file)
LOG_LEVEL=warning

QUEUE_CONNECTION=database                  # jobs stored in DB until the worker runs
CACHE_STORE=database

# === Cloudflare trusted proxies ===
# The app should see the REAL client IP, not Cloudflare's edge IP.
# Set TRUSTED_PROXIES to Cloudflare's published IPv4
# ranges so Laravel trusts X-Forwarded-For from them. See:
# https://www.cloudflare.com/ips/
# IMPORTANT: this is read via config('trustedproxy.proxies') (config/trustedproxy.php),
# NOT a bare env() call, so it keeps working after `php artisan config:cache`.
# Example (update to the current Cloudflare ranges):
# TRUSTED_PROXIES=173.245.48.0/20,103.21.244.0/22,103.22.200.0/22,103.31.4.0/22,141.101.64.0/18,108.162.192.0/18,190.93.240.0/20,188.114.96.0/20,197.234.240.0/22,198.41.128.0/17,162.158.0.0/15,104.16.0.0/13,104.24.0.0/14,172.64.0.0/13,131.0.72.0/22

# === GCash manual payments ===
# No env vars needed. After logging in as an admin, set the GCash mobile
# number, account name and QR image under Admin → Settings → GCash Payment.
```

Then cache the config: `php artisan config:cache`.

Trusted proxies load through `config('trustedproxy.proxies')` (from
`config/trustedproxy.php`), applied by `App\Http\Middleware\TrustProxies` at
handle-time, so they survive `config:cache`. Do not read `TRUSTED_PROXIES` via a
bare `env()` call in `bootstrap/app.php` -- cached-config mode makes `env()`
return null outside config files, which silently disables proxy trust and makes
`$request->ip()` report the proxy address.

## 5. Cloudflare setup

- Add the domain to Cloudflare (DNS). Point `A` + `AAAA` records to the VPS IP.
- **SSL/TLS mode: Full (strict)**
  - The origin (nginx) must present a **valid** cert. Use Let's Encrypt on the
    VPS (`certbot --nginx -d pickmeretailers.com -d www.pickmeretailers.com`), or
    Cloudflare Origin CA.
- Keep `www`/apex serving HTTPS. The `.env` `APP_URL` should be the canonical
  host.

## 6. Supervisor (queue + scheduler)

Copy `deploy/supervisor/*.conf` to `/etc/supervisor/conf.d/`, adjusting
`user`, `command` paths, and the PHP binary to match the server.

```bash
sudo cp deploy/supervisor/laravel-queue.conf deploy/supervisor/laravel-schedule.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

Verify both are `RUNNING`. This is required — without the worker, queued
emails never run; without the scheduler, unpaid orders
never expire.

## 7. nginx + deployment

- Copy `deploy/nginx/ecommerce.conf` into nginx, adjust the PHP-FPM socket
  version and paths, then reload.
- The deployed `public/.user.ini` sets PHP-FPM upload limits to 10 MB per file
  and 12 MB per request. Keep nginx's `client_max_body_size` at 12 MB or more
  (as in the supplied config) so requests reach PHP for validation.
- Remove the local Vite dev marker so production serves built assets:
  `rm -f public/hot`
- Build assets: `npm ci && npm run build`
- Install prod deps: `composer install --no-dev --classmap-authoritative`
- Cache routes + config: `php artisan config:cache && php artisan route:cache`
- Storage link: `php artisan storage:link`

## 8. Maintenance mode (optional)

To keep the site hidden while you finish configuration, use the branded
maintenance page (`resources/views/errors/503.blade.php`):

```bash
php artisan down
```

- The `/up` health endpoint stays reachable (see nginx config), so Cloudflare
  health checks keep passing.
- There is no sandbox gateway to test against; place a test order and confirm
  it through the GCash admin queue (see go-live below).

## GO-LIVE

1. Take the site live: `php artisan up`
2. Log in as an admin and set the GCash mobile number, account name and QR
   image under **Settings → GCash Payment**.
3. Place one real order to verify the flow: the customer opens **Orders → Pay**,
   submits the GCash reference (and optional screenshot), then an admin confirms
   it from **Admin → Payments**. Confirmation marks the order paid, issues the
   invoice and writes the transaction ledger entry.

No gateway keys or code changes are required — GCash payments are verified
manually by an admin.
