#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../../.."

# Detect a CLI PHP binary compatible with this app (requires >= 8.3).
find_php_bin() {
  local candidates=(
    "${PHP_BIN:-}"
    "$(command -v php8.4 2>/dev/null || true)"
    "$(command -v php84 2>/dev/null || true)"
    "$(command -v php8.3 2>/dev/null || true)"
    "$(command -v php83 2>/dev/null || true)"
    "/opt/alt/php84/usr/bin/php"
    "/opt/alt/php83/usr/bin/php"
    "/opt/cpanel/ea-php84/root/usr/bin/php"
    "/opt/cpanel/ea-php83/root/usr/bin/php"
    "$(command -v php 2>/dev/null || true)"
  )

  local bin major minor
  for bin in "${candidates[@]}"; do
    [[ -n "${bin}" && -x "${bin}" ]] || continue
    major="$(${bin} -r 'echo PHP_MAJOR_VERSION;' 2>/dev/null || true)"
    minor="$(${bin} -r 'echo PHP_MINOR_VERSION;' 2>/dev/null || true)"
    if [[ -n "${major}" && -n "${minor}" ]]; then
      if [[ "${major}" -gt 8 || ( "${major}" -eq 8 && "${minor}" -ge 3 ) ]]; then
        echo "${bin}"
        return 0
      fi
    fi
  done

  return 1
}

PHP_CMD="$(find_php_bin || true)"
if [[ -z "${PHP_CMD}" ]]; then
  echo "ERROR: No PHP 8.3+ CLI binary found."
  echo "Hint: On shared hosting, hPanel web PHP version does not always change SSH CLI PHP."
  echo "Set PHP_BIN manually, e.g.: PHP_BIN=/opt/alt/php84/usr/bin/php bash deployment/hostinger/shared/deploy.sh"
  exit 1
fi

echo "Using PHP CLI: ${PHP_CMD}"
"${PHP_CMD}" -v | head -n 1

COMPOSER_CMD="$(command -v composer || true)"

echo "[1/8] Installing PHP dependencies"
if [[ -n "${COMPOSER_CMD}" ]]; then
  "${PHP_CMD}" "${COMPOSER_CMD}" install --no-dev --optimize-autoloader --no-interaction
else
  echo "ERROR: composer command not found in PATH."
  exit 1
fi

echo "[2/8] Generating app key if missing"
if [[ -z "${APP_KEY:-}" ]] && grep -q '^APP_KEY=$' .env; then
  "${PHP_CMD}" artisan key:generate --force
fi

echo "[3/8] Running migrations"
"${PHP_CMD}" artisan migrate --force

echo "[4/8] Linking storage"
"${PHP_CMD}" artisan storage:link || true

# Remove Vite dev server hot file if accidentally left on server.
# Its presence forces Laravel to load assets from localhost:5173 instead of public/build.
rm -f public/hot

echo "[5/8] Building frontend assets"
if command -v npm >/dev/null 2>&1; then
  npm ci
  npm run build
else
  echo "npm not found on server. Skipping build."
  if [[ -f public/build/.vite/manifest.json ]]; then
    echo "Found existing public/build manifest. Continuing deploy with prebuilt assets."
  else
    echo "ERROR: public/build/.vite/manifest.json not found."
    echo "Build assets locally/CI and upload public/build, then rerun deploy."
    exit 1
  fi
fi

echo "[6/8] Caching config/routes/views"
"${PHP_CMD}" artisan config:cache
"${PHP_CMD}" artisan route:cache
"${PHP_CMD}" artisan view:cache

echo "[7/8] Optimizing app"
"${PHP_CMD}" artisan optimize

echo "[8/8] Setting write permissions"
chmod -R 775 storage bootstrap/cache || true

echo "Deployment completed successfully."
