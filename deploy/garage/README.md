# CI deploy — garage.bangicode.ma (Namecheap cPanel, SSH)

Automated demo/staging deploy of Direct Auto Care to **garage.bangicode.ma** on
the Namecheap cPanel account (`premium173.web-hosting.com`).

**How it works:** on every push to `main`, once the **CI** workflow passes, the
**Deploy** workflow (`.github/workflows/deploy.yml`) builds the front-end
(`npm ci && npm run build`) and PHP deps (`composer install --no-dev`), `rsync`s
the result to the server over SSH, then runs `deploy/garage/post-deploy.sh`
(migrate + cache) remotely. You can also trigger it manually from the Actions tab.

> **Nothing deploys until you finish §1–§3 and set `DEPLOY_ENABLED=true`.** The
> deploy job is gated on that repo variable, so merging this PR is safe.

---

## 1. cPanel setup (one-time — only you can do this)

> The cPanel session link is one-time and sensitive — don't paste it anywhere.

1. **Subdomain** → cPanel ▸ *Domains* / *Subdomains* ▸ Create:
   - Subdomain `garage`, Domain `bangicode.ma`
   - **Document Root:** `garage/public` ← point at Laravel's `public/`, not the
     app root (keeps `.env`, `vendor/`, DB code out of the web root). This makes
     the app root `~/garage` and serves from `~/garage/public`.
2. **PHP 8.4** → cPanel ▸ *MultiPHP Manager* ▸ set `garage.bangicode.ma` to PHP 8.4.
3. **Database** → cPanel ▸ *MySQL Databases*: create a **dedicated** DB + user
   (e.g. `garage` / `garageusr`, prefixed to `cpuser_…`), grant ALL PRIVILEGES.
   Record the prefixed names + password for the server `.env`.
4. **SSH access** → cPanel ▸ *SSH Access* ▸ *Manage SSH Keys*:
   - Generate (or import) a key pair **dedicated to deploys**, then **Authorize**
     the public key. Download the **private** key — it goes into the GitHub
     secret `CPANEL_SSH_KEY` (§2).
   - Note the SSH **host** (`premium173.web-hosting.com`), **port** (Namecheap
     shared is usually **21098**), and **username** (your cPanel user).
   - Verify locally: `ssh -p 21098 cpuser@premium173.web-hosting.com` →
     `composer --version` is optional (vendor/ is uploaded), but confirm the
     PHP 8.4 CLI path: `ls /opt/cpanel/ea-php84/root/usr/bin/php`.
5. **AutoSSL** → cPanel ▸ *SSL/TLS Status* ▸ tick `garage.bangicode.ma` ▸ Run
   AutoSSL (so `https://garage.bangicode.ma` gets a cert).

## 2. GitHub secrets & variables (Settings ▸ Secrets and variables ▸ Actions)

**Secrets:**

| Secret | Value |
|---|---|
| `CPANEL_SSH_HOST` | `premium173.web-hosting.com` |
| `CPANEL_SSH_PORT` | `21098` (confirm in cPanel ▸ SSH Access) |
| `CPANEL_SSH_USER` | your cPanel username |
| `CPANEL_SSH_KEY` | the **private** key from §1.4 (full PEM, incl. BEGIN/END lines) |
| `CPANEL_DEPLOY_PATH` | absolute app root, e.g. `/home/<cpuser>/garage` |
| `CPANEL_ENV` | the full server `.env` contents — CI writes this to `$CPANEL_DEPLOY_PATH/.env` on every deploy. Must include a fixed `APP_KEY=base64:…`, the DB block, and `ADMIN_*`. |

**Variables:**

| Variable | Value |
|---|---|
| `CPANEL_PHP_BIN` | `/opt/cpanel/ea-php84/root/usr/bin/php` (adjust to the real path) |
| `DEPLOY_ENABLED` | leave unset for now; set to `true` after §3 to arm deploys |

## 3. First-time go-live (no manual `.env` step)

The deploy **writes `.env` on the server from the `CPANEL_ENV` secret** on every
run, so there's nothing to hand-edit. To go live:

1. Set the **`DEPLOY_ENABLED`** variable to `true`.
2. Point the subdomain **Document Root** at `<CPANEL_DEPLOY_PATH>/public`
   (e.g. `/home/bangspbp/garage/public`).
3. Ensure the **PHP 8.4 CLI has `pdo_mysql`** (cPanel ▸ *Select PHP Version* ▸
   *Extensions*), and the docroot `.htaccess` PHP handler is **scoped to PHP files**:
   ```apache
   <FilesMatch "\.(php|phtml)$">
       SetHandler application/x-httpd-alt-php84
   </FilesMatch>
   ```
   (an unscoped `SetHandler` 404s non-PHP routes like `/login`).
4. Run the Deploy workflow with the **`reseed`** input **checked**
   (Actions ▸ Deploy ▸ *Run workflow*). It writes `.env`, runs
   `migrate:fresh --seed --force` (schema + DEMO data), links storage, caches.
5. Run **AutoSSL** (§1.5) → open `https://garage.bangicode.ma` and log in with the
   `ADMIN_EMAIL` / `ADMIN_PASSWORD` baked into `CPANEL_ENV`.

From then on, **every push to `main` auto-deploys** after CI passes:
`post-deploy.sh` runs `migrate --force` (additive — demo data persists). To reset
the demo to clean fake data, re-run the workflow with **reseed** checked.

---

## What the pipeline does / doesn't

- **Builds in CI** (Ubuntu): `npm ci && npm run build`, `composer install --no-dev`
  → `vendor/` + `public/build/` are uploaded; the host needs no Node/Composer.
- **rsync excludes** (`rsync-exclude.txt`) protect the server's `.env` and
  `storage/`; no `--delete`, so deploys never remove server runtime files.
- **Demo posture** (`.env.staging.example`): `APP_DEBUG=false`, mail→log,
  WhatsApp disabled, `QUEUE_CONNECTION=sync`. No queue worker / scheduler.

## Troubleshooting

- **Deploy job skipped** → `DEPLOY_ENABLED` isn't `true`, or it wasn't a CI
  success on `main` / a manual run.
- **`Permission denied (publickey)`** → wrong `CPANEL_SSH_KEY`/`USER`/`PORT`, or
  the public key isn't *Authorized* in cPanel.
- **`storage:link` warning** → host blocks symlinks; create it manually:
  `ln -s ~/garage/storage/app/public ~/garage/public/storage`.
- **Old PHP errors** → `CPANEL_PHP_BIN` points at the wrong CLI; fix the path.
- **500 / blank page** → check `~/garage/storage/logs/laravel.log`; ensure
  `.env` exists with a valid `APP_KEY` and DB creds, and docroot is `garage/public`.
