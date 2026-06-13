#!/usr/bin/env bash
# =============================================================================
# Run ON THE cPanel SERVER (Terminal or SSH), from the app root, after the
# files are uploaded and .env is in place.
#
#   bash deploy/staging/post-deploy.sh
#
# IMPORTANT: cPanel's default `php` in Terminal is often an OLD version. Set
# PHP_BIN to the 8.2/8.3 binary first, e.g.:
#   export PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php
#   (or: export PHP_BIN=$(which ea-php83 || which php83 || which php))
# =============================================================================
set -euo pipefail

PHP_BIN="${PHP_BIN:-php}"
echo "Using PHP: $("$PHP_BIN" -v | head -1)"

# 1. App key (only writes if missing/empty)
if ! grep -qE '^APP_KEY=base64:' .env; then
  "$PHP_BIN" artisan key:generate --force
fi

# 2. Build the schema and load FAKE demo data.
#    migrate:fresh DROPS ALL TABLES in the staging DB first — safe here because
#    this DB is dedicated to the demo. Never point .env at the client's DB.
"$PHP_BIN" artisan migrate:fresh --seed --force

# 3. Storage symlink for any uploaded/public files (logo, etc.).
#    If symlinks are disabled on the host this will warn — see README §8.
"$PHP_BIN" artisan storage:link || echo "storage:link skipped (symlinks may be disabled) — see README §8"

# 4. Cache config/routes/views for production-grade performance.
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

echo
echo "Done. Visit \$APP_URL and log in with ADMIN_EMAIL / ADMIN_PASSWORD from .env."
echo "To RESET the demo to clean fake data later, re-run step 2:"
echo "  $PHP_BIN artisan migrate:fresh --seed --force"
