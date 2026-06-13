# Staging / demo deploy — cPanel shared hosting

Deploy a public **demo** of Direct Auto Care, with fake seeded data, on a
subdomain of `directonderweg.com`, fully isolated from the client project that
already lives on that account.

Target subdomain (suggested): `garage-demo.directonderweg.com`

> **Why these steps look the way they do.** Shared cPanel hosting has no Node,
> can't run persistent queue workers, and shares one account's resources across
> every site on it. So we **build assets locally and upload**, run jobs
> **inline (`sync`)**, and take pains to keep this demo from touching the
> client's site or firing real messages. Read §7 and §11 before going live.

---

## 0. Before you start — decisions & facts to gather

| Need | Where to find it |
|---|---|
| Is the domain's DNS managed *by cPanel* or *elsewhere* (Cloudflare, registrar)? | cPanel → Zone Editor, or your registrar |
| Which PHP versions does the host offer? | cPanel → **MultiPHP Manager** / "Select PHP Version" (need ≥ 8.2) |
| Is **Terminal**/SSH available? Is **composer** installed? | cPanel → Terminal; run `composer --version` |
| The server IP (only if DNS is managed elsewhere) | cPanel → right sidebar "Shared IP Address" |

If the host has **no PHP 8.2+** or **no Terminal/SSH + no composer**, stop and
tell Ahmed — this stack won't deploy cleanly there and a small VPS / Laravel
Cloud demo environment is the better route.

---

## 1. Create the subdomain with the right document root

cPanel → **Domains** (or **Subdomains**) → Create:

- **Subdomain:** `garage-demo`
- **Domain:** `directonderweg.com`
- **Document Root:** `garage-demo/public`  ← point it at Laravel's `public/`,
  **not** the app root. This keeps `.env`, `vendor/`, and the DB code out of the
  web root.

This creates `~/garage-demo/` as the app folder and serves the site from
`~/garage-demo/public`. Because the domain is on this cPanel account, the DNS
**A record is created automatically**.

> **If DNS is managed elsewhere** (e.g. Cloudflare): add an `A` record
> `garage-demo` → *server IP* (proxy/orange-cloud OFF for the first AutoSSL
> issuance, then you can turn it back on).
>
> **If the host forces the docroot under `public_html`** and won't let you set
> `garage-demo/public`: put the app at `~/garage-demo` anyway and, in
> `~/public_html/garage-demo`, add an `.htaccess` that rewrites into
> `../garage-demo/public`. Ask and I'll provide it — the clean docroot above is
> strongly preferred.

---

## 2. Set the PHP version for the subdomain

cPanel → **MultiPHP Manager** → select `garage-demo.directonderweg.com` → set to
**PHP 8.3** (or 8.2). Leave the client's domain on whatever it already uses —
MultiPHP is per-domain, so this won't affect their site.

---

## 3. Create a dedicated database

cPanel → **MySQL® Databases**:

1. Create database: `garagestg` → becomes `cpuser_garagestg`
2. Create user: `garage` → becomes `cpuser_garage`, with a strong password
3. **Add user to database** → grant **ALL PRIVILEGES**

Record the prefixed names + password for the `.env` DB block. **Never reuse the
client project's database or DB user.**

---

## 4. Build locally (Windows / PowerShell)

Shared hosting has no Node, so compile the front-end on your machine first.
From the project root:

```powershell
# Use the exact, reproducible front-end build
npm ci
npm run build                      # writes public/build/ (manifest + assets)

# Production PHP deps WITHOUT dev tooling (no Boost, no PHPUnit on the server)
composer install --no-dev --optimize-autoloader --no-interaction
```

> If `composer` isn't on your PATH locally but **is** available in cPanel
> Terminal, you can skip the local `composer install` and run it on the server
> in step 6 instead (preferred — guarantees the right platform). Either way,
> `vendor/` for this stack is pure PHP and uploads fine.

---

## 5. Package & upload

Upload **everything except** local-only cruft. Exclude:
`node_modules/`, `.git/`, `tests/`, `.env`, `storage/*.key`, the throwaway
screenshots in the repo root, and `database/*.sqlite`.

PowerShell zip (run from project root):

```powershell
$exclude = @('node_modules','.git','tests','.env')
$items = Get-ChildItem -Force | Where-Object { $_.Name -notin $exclude `
  -and $_.Extension -notin '.png','.jpeg' }
Compress-Archive -Path $items -DestinationPath ..\garage-demo.zip -Force
```

Then cPanel → **File Manager** → go to `~/garage-demo` → **Upload**
`garage-demo.zip` → **Extract** there. (Or use SFTP / `scp` if you prefer.)

Confirm the layout on the server:
```
~/garage-demo/app  artisan  bootstrap  public  vendor  ...   ← app root
~/garage-demo/public/build/manifest.json                     ← built assets exist
```

---

## 6. Configure env and initialise the demo data

In cPanel **Terminal** (or SSH), from `~/garage-demo`:

```bash
# Put the staging env in place, then edit the CHANGE-ME values
cp deploy/staging/.env.staging.example .env
nano .env        # set APP_URL, SESSION_DOMAIN, DB_*, ADMIN_PASSWORD

# Pick the correct PHP CLI (cPanel's default `php` is often old!)
export PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php   # adjust to your version

# If you did NOT run composer locally, do it here:
# composer install --no-dev --optimize-autoloader --no-interaction

# Generate key, build schema, load FAKE data, cache config
bash deploy/staging/post-deploy.sh
```

`post-deploy.sh` runs `migrate:fresh --seed --force`, which **drops and
rebuilds** the staging DB with seeded fake data (20 customers, 50 vehicles,
invoices, appointments, reminders, and the demo admin). Re-run that one command
any time to reset the demo to a clean state.

---

## 7. WhatsApp on staging — testable, but only ever to YOUR number

`routes/console.php` schedules `reminders:dispatch` daily at 08:00, which sends
WhatsApp reminders to customer phone numbers. On the demo those are
**faker-generated real-looking +212 Moroccan numbers**, so they must never be
messaged. The app supports a recipient **redirect** to make this safe to test:

- `META_WA_REDIRECT_TO` (set to `+212626410690` in the template) routes **every**
  outgoing WhatsApp — for every seeded client — to that one number. The message
  template still shows the real (fake) client's name and plate, so you can see
  exactly what each customer *would* receive, all delivered to your phone.
- Fill `META_WA_PHONE_NUMBER_ID` / `META_WA_ACCESS_TOKEN` with your Meta
  WhatsApp Cloud API credentials to actually send. **Leave them blank to keep
  WhatsApp fully disabled** (the channel fails closed — nothing is sent).
- In your Meta app, add `+212626410690` to the **allowed recipient list**
  ("API Setup" → *To*) until your WhatsApp Business number is fully approved,
  or Meta rejects the send with error `131047`.

### Triggering a test send

Once creds + redirect are set, fire the dispatcher manually from Terminal:

```bash
export PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php
$PHP_BIN artisan reminders:dispatch     # sends all DUE reminders -> your number
```

Because of the redirect, this is safe even if you later add a `schedule:run`
cron — but for a controlled demo, prefer triggering it by hand as above.

> **Production note:** `META_WA_REDIRECT_TO` MUST stay blank in any real
> production env, or every customer's reminder would be delivered to that one
> number. It is a staging-only switch.

---

## 8. Storage symlink (if `storage:link` warned)

If the host disables symlinks, `php artisan storage:link` fails. Fallback —
create it manually in Terminal:

```bash
ln -s ~/garage-demo/storage/app/public ~/garage-demo/public/storage
```

If symlinks are entirely blocked, the app still runs; only user-uploaded public
files (e.g. a company logo) would 404. Not relevant for a fresh demo.

---

## 9. Lock the demo down (it's on a client domain)

cPanel → **Directory Privacy** → navigate to `~/garage-demo/public` → enable
**"Password protect this directory"** and add a user. This puts HTTP Basic Auth
in front of the whole demo, so it isn't publicly browsable or indexed on the
client's domain. Share the basic-auth user + the demo admin login with whoever
needs to see it.

`APP_DEBUG=false` (set in the template) ensures no stack traces leak even if
something errors.

---

## 10. HTTPS

cPanel → **SSL/TLS Status** → tick `garage-demo.directonderweg.com` → **Run
AutoSSL**. New subdomains on an account with AutoSSL usually get a cert within
minutes. Confirm `https://garage-demo.directonderweg.com` loads with a valid
padlock; `APP_URL` is already `https://` in the template.

---

## Updating the demo later

After code changes:

```powershell
npm ci; npm run build
composer install --no-dev --optimize-autoloader --no-interaction
# re-zip & upload as in §5 (you can skip node_modules/vendor if unchanged)
```

On the server:
```bash
export PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php
$PHP_BIN artisan migrate --force          # or migrate:fresh --seed --force to reset
$PHP_BIN artisan config:cache && $PHP_BIN artisan route:cache && $PHP_BIN artisan view:cache
```

---

## 11. Caveats to keep in mind

- **Shared resources.** This demo runs inside the *client's* cPanel account, so
  it shares their CPU/RAM/disk quota and PHP-FPM pool. A low-traffic demo is
  fine, but if their site is busy or quota is tight, prefer a separate cPanel
  account, a cheap VPS, or a Laravel Cloud environment instead.
- **It appears under their domain.** `garage-demo.directonderweg.com` is visibly
  a subdomain of the client's site. Make sure that's acceptable to them; a
  neutral domain avoids any brand confusion.
- **Fake data only.** The seeders use faker — no real PII. Don't import real
  customer data into this environment.
- **Not production.** No queue worker, mail goes to the log, debug off, and
  WhatsApp is redirected to a single test number (§7). These are deliberate for
  a demo and must not be carried into a real production deploy — in particular
  `META_WA_REDIRECT_TO` must be blank in production.
