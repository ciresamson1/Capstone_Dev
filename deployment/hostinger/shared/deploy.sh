#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../../.."

echo "[1/8] Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "[2/8] Generating app key if missing"
if [[ -z "${APP_KEY:-}" ]] && grep -q '^APP_KEY=$' .env; then
  php artisan key:generate --force
fi

echo "[3/8] Running migrations"
php artisan migrate --force

echo "[4/8] Linking storage"
php artisan storage:link || true

echo "[5/8] Building frontend assets"
if command -v npm >/dev/null 2>&1; then
  npm ci
  npm run build
else
  echo "npm not found on server. Upload prebuilt public/build from CI or local machine."
fi

echo "[6/8] Caching config/routes/views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[7/8] Optimizing app"
php artisan optimize

echo "[8/8] Setting write permissions"
chmod -R 775 storage bootstrap/cache || true

echo "Deployment completed successfully."
