# 🚀 Production Deployment Checklist

**Project:** Pick Me Retailers  
**Date:** 2026-08-27  
**Status:** In progress — VPS + Cloudflare (Full strict) + supervisor. Payments are collected manually via GCash and verified by an admin, so there is no gateway approval to wait on.

---

## ✅ Phase 1: Secrets Cleanup - COMPLETED

- [x] `.env` removed from Git history using `git filter-branch`
- [x] Repository force-pushed with cleaned history
- [x] Verified no credentials in version control
- [x] `.env.example` exists for local development reference
- [x] `.env` already in `.gitignore`

**Result:** Repository is now safe for public/team access. `.env` credentials will NOT be exposed to new clones.

---

## ⏳ Phase 2: Production Configuration

### Step 1: Rotate Compromised Secrets
**Status:** 🔴 NOT DONE  
**Reason:** Database password was exposed in Git history  
**Action Required:**

```bash
# ⚠️ CRITICAL: Do this IMMEDIATELY
# 1. Access your MySQL database server
mysql -h 127.0.0.1 -u root -p

# 2. Generate a new password and update the database user:
ALTER USER 'root'@'127.0.0.1' IDENTIFIED BY 'NEW_SECURE_PASSWORD_HERE';
FLUSH PRIVILEGES;

# 3. Update your local .env file:
# DB_PASSWORD=NEW_SECURE_PASSWORD_HERE

# 4. Test connection:
php artisan tinker
# Then: DB::connection()->getPdo()
# Should connect without error
```

**Other Exposed Credentials to Rotate:**
- ✅ `MAIL_PASSWORD` - Generate new App Password in Gmail settings
- ✅ `GOOGLE_MAPS_API_KEY` - Create new API key in Google Cloud Console
- ✅ `APP_KEY` - Already unique (generated with `php artisan key:generate`)

---

### Step 2: Verify Production Mode Settings
**File:** `.env`  
**Checklist:**

```bash
# Run this command and verify output:
php artisan tinker

# Check these values:
config('app.env')           # Should be: 'production'
config('app.debug')         # Should be: false
config('mail.from.name')    # Should be: 'Pick Me Retailers'
config('queue.default')     # Should be: 'database' (or 'redis' for scale)
config('cache.default')     # Should be: 'database' (or 'redis' for scale)
config('session.secure')    # Should be: true
config('session.encrypt')   # Should be: true
config('auth.passwords.users.expire') # Should be set
```

**Required `.env` values for production (set on the VPS — secret values are
redacted from this repo and never committed):**
- [x] `APP_ENV=production` - Correct
- [x] `APP_DEBUG=false` - Correct  
- [ ] `APP_URL=https://pickmeretailers.com` - Set to real domain (currently ngrok)
- [ ] `DB_USERNAME`/`DB_PASSWORD` - Use dedicated `app_user`, rotated password
- [ ] `MAIL_PASSWORD` - Rotated Gmail App Password
- [ ] `GOOGLE_MAPS_API_KEY` - rotated + domain-restricted key
- [ ] `TRUSTED_PROXIES` - Cloudflare IP ranges (see deploy/README.md)

---

### Step 3: Configure GCash Payment Details

Payments are collected manually through GCash, so there are no gateway API keys
to configure. After the admin account exists:

1. Log in as an admin.
2. Open **Settings → GCash Payment**.
3. Enter the GCash mobile number and account name.
4. Optionally edit the customer-facing instructions.
5. Upload the GCash QR code image (JPG, PNG or WebP).

Customers see these details on the order payment page and submit their GCash
reference there; an admin confirms or rejects each payment from
**Admin → Payments**.

**Test:**
```bash
php artisan tinker
App\Models\Setting::get('gcash_number')   # Verify the saved number
```

---

### Step 4: Configure Trusted Proxies (If Behind Load Balancer/CDN)

**Scenario 1: Behind Cloudflare CDN**
```env
# Set in .env to Cloudflare's published IPv4 ranges (see deploy/README.md).
# Do NOT use "*" — that lets anyone spoof X-Forwarded-For.
TRUSTED_PROXIES=<Cloudflare IP ranges from https://www.cloudflare.com/ips/>
```

**Scenario 2: Behind AWS Application Load Balancer**
```env
TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12,192.168.0.0/16
```

**Scenario 3: Behind AWS API Gateway / Lambda**
```env
TRUSTED_PROXIES=*
```

**Why this matters:** Behind a proxy, Laravel reads the proxy's IP unless it
trusts `X-Forwarded-For` from that proxy, so logs, rate limits and any IP-based
logic would see the wrong client address.

**Test:**
```bash
# After deployment, check what IP Laravel sees:
php artisan tinker
request()->ip()  # Should be the real client IP, not the proxy IP
```

---

## ⏳ Phase 3: Dependency & Build Validation

### Step 1: Check for Dependency Vulnerabilities  
**Status:** ✅ DONE (2026-08-27)  
**Command:**

```bash
# Scan composer dependencies for CVEs:
composer audit
```

**Result:** No security vulnerability advisories found (0 CVEs).

**If vulnerabilities found:**
```bash
# Update vulnerable packages:
composer update

# Re-run audit:
composer audit
```

---

### Step 2: Build Frontend Assets
**Status:** 🔴 NOT DONE  
**Commands:**

```bash
# Install Node dependencies:
npm install

# Build production assets:
npm run build

# Verify build output:
ls -la public/build/
# Should contain manifest.json and asset files
```

---

### Step 3: Cache Configuration
**Status:** 🔴 Run at deploy (done now that route closures were converted)  
**Commands:**

```bash
# Cache configuration files (improves performance):
php artisan config:cache

# Cache routes (improves performance). Works now — all route closures were
# converted to controller methods so routes are serializable:
php artisan route:cache

# Optimize autoloader (improves performance):
composer install --optimize-autoloader --no-dev
```

---

## 🧪 Phase 4: Pre-Deployment Testing

### Step 1: Verify Application Startup
```bash
# Run development server to verify:
php artisan serve --host=127.0.0.1 --port=8000

# In browser: http://localhost:8000
# Should load homepage without errors
# Check Laravel logs: tail -f storage/logs/laravel.log
```

---

### Step 2: Test the GCash Payment Flow
There is no webhook to test — payment confirmation is manual. Run a real
end-to-end order:

1. Place an order as a customer and land on the order payment page.
2. Confirm the GCash number/name/QR from **Settings → GCash Payment** are shown.
3. Submit a GCash reference (and screenshot) — the order becomes
   `awaiting_verification`.
4. As an admin, open **Admin → Payments** and confirm it. The order is marked
   paid, an invoice is generated, a ledger entry is written, the cart is cleared
   and a confirmation email is queued.
6. Check **Admin → Transactions** for the recorded payment.

---

### Step 3: Test Database Connection
```bash
php artisan tinker

# Run:
DB::select('SELECT 1 as id')

# Expected: Collection with id=>1
```

---

### Step 4: Test Email Sending
```bash
php artisan tinker

# Run:
Mail::raw('Test email', function ($message) {
  $message->to('your-email@example.com');
});

# Check your email inbox (may take 1-2 minutes)
```

---

## 📋 Final Deployment Steps

### Before Going Live:

1. [ ] All Phase 2 steps completed
2. [ ] **Supervisor running both programs:** `laravel-queue` AND `laravel-schedule` (see `deploy/supervisor/`). Without these, queued emails and the unpaid-order expiry never run.
3. [ ] All Phase 4 tests passed
4. [ ] Database backed up
5. [ ] Rollback plan documented
6. [ ] Uptime monitoring configured
7. [ ] Error monitoring (Sentry/Rollbar) configured — optional, not yet wired
8. [ ] SSL certificate verified (HTTPS working) — Cloudflare Full (strict) + origin cert
9. [ ] Rate limiting tested
10. [ ] Security headers verified (check in browser DevTools) — CSP is Report-Only
11. [ ] `public/hot` removed on deploy (serve from `public/build`)

### Deployment Commands:
```bash
# On production server:

# 1. Pull latest code:
git pull origin main

# 2. Install dependencies (prod only, no dev tools like tinker):
composer install --optimize-autoloader --no-dev

# 3. Run migrations:
php artisan migrate --force

# 4. Build assets + remove Vite dev marker:
rm -f public/hot
npm ci && npm run build

# 5. Cache configuration and routes:
php artisan config:cache
php artisan route:cache
php artisan storage:link

# 6. Restart supervisord programs (queue + scheduler):
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all

# 7. Take the site live (or keep it down while configuring GCash):
php artisan up
```

---

## 🔐 Security Verification Checklist

- [ ] `.env` file NOT visible in public repositories
- [ ] Database credentials rotated
- [ ] API keys are production keys (not sandbox)
- [ ] `APP_DEBUG=false` in production
- [ ] HTTPS enforced (redirect HTTP → HTTPS)
- [ ] SSL certificate valid and not expired
- [ ] Security headers present (check with: `curl -I https://pickmeretailers.com`)
- [ ] Rate limiting active
- [ ] GCash payment details configured (Admin → Settings → GCash Payment)
- [ ] Admin accounts use strong passwords
- [ ] Two-factor authentication enabled for admin accounts (recommended)

---

## 📞 Rollback Plan

If issues occur after deployment:

```bash
# View recent commits:
git log --oneline -10

# Revert to previous version:
git revert <commit-hash>
# OR
git reset --hard <commit-hash>

# Restart services:
php artisan cache:clear
php artisan queue:restart
supervisorctl restart all
```

---

## 📞 Emergency Contacts

- **GCash Merchant Support:** https://help.gcash.com
- **Laravel Docs:** https://laravel.com/docs
- **Security Issues:** Contact your hosting provider immediately

---

**Last Updated:** 2026-08-27  
**Next Review:** At each deployment
