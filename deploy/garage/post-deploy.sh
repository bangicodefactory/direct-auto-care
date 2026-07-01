#!/usr/bin/env bash
# =============================================================================
# Server-side post-deploy for garage.bangicode.ma (run by CI over SSH, and safe
# to run by hand from the app root). SAFE TO RUN ON EVERY DEPLOY: it applies new
# migrations additively and never drops data.
#
#   cd ~/garage && PHP_BIN=/opt/cpanel/ea-php84/root/usr/bin/php bash deploy/garage/post-deploy.sh
#
# cPanel's default `php` in Terminal is often an OLD version — set PHP_BIN to the
# PHP 8.4 CLI (CI passes it via the CPANEL_PHP_BIN repo variable). The project's
# locked deps (Symfony v8) require PHP >= 8.4.
#
# To (re)load the DEMO fake data / reset the showcase, run ONCE manually instead:
#   $PHP_BIN artisan migrate:fresh --seed --force      # DROPS ALL TABLES, then seeds
# =============================================================================
set -euo pipefail

PHP_BIN="${PHP_BIN:-php}"
echo "Using PHP: $("$PHP_BIN" -v | head -1)"

# Ensure writable runtime dirs exist (storage/ is intentionally NOT rsynced).
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
         storage/logs bootstrap/cache

# Clear any stale config cache from a previous deploy BEFORE the seeders run.
# The seeders read ADMIN_EMAIL / ADMIN_PASSWORD via env(), and Laravel makes
# env() return null whenever a cached config file is present (env is only read
# into config during config:cache). Without this, migrate:fresh --seed creates
# the admin with the hard-coded fallback creds instead of the ones in .env.
"$PHP_BIN" artisan config:clear || true

# App key — only generate if .env doesn't already have one.
if ! grep -qE '^APP_KEY=base64:' .env; then
  "$PHP_BIN" artisan key:generate --force
fi

# Apply migrations. SEED=1 (the deploy workflow's "reseed" input) rebuilds the
# demo — migrate:fresh --seed DROPS ALL TABLES then loads fake data. Otherwise
# migrate --force is additive and never drops data.
if [ "${SEED:-0}" = "1" ]; then
    echo "SEED=1 -> resetting demo data (migrate:fresh --seed)"
    "$PHP_BIN" artisan migrate:fresh --seed --force
else
    "$PHP_BIN" artisan migrate --force
fi

# Public storage symlink (company logo, reception photos, signatures).
"$PHP_BIN" artisan storage:link || echo "storage:link skipped (symlinks may be disabled) — see README"

# Rebuild caches for production performance (clear stale ones from the last deploy first).
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

echo "Post-deploy complete${CLIENT:+ for $CLIENT}."
