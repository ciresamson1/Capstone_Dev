#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../../.."

echo "[1/9] Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "[2/9] Generating app key if missing"
if [[ -z "${APP_KEY:-}" ]] && grep -q '^APP_KEY=$' .env; then
  php artisan key:generate --force
fi

echo "[3/9] Running migrations"
php artisan migrate --force

echo "[4/9] Linking storage"
php artisan storage:link || true

echo "[5/9] Installing/building frontend"
npm ci
npm run build

echo "[6/9] Caching config/routes/views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[7/9] Optimizing app"
php artisan optimize

echo "[8/9] Setting permissions"
chmod -R 775 storage bootstrap/cache

echo "[9/9] Restarting Supervisor services"
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart laravel-worker:*
sudo supervisorctl restart laravel-reverb

echo "Deployment completed successfully."
