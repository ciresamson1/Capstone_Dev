#!/bin/bash
set -e

# Navigate to project root
cd "$(dirname "$0")"

# 1. Install Composer dependencies (production only)
composer install --no-dev --optimize-autoloader 2>&1 | tee -a storage/logs/deploy.log

# 2. Generate app key (only if missing)
if ! grep -q "^APP_KEY=" .env; then
    php artisan key:generate
fi

# 3. Run migrations
php artisan migrate --force 2>&1 | tee -a storage/logs/deploy.log

# 4. Create storage symlink
php artisan storage:link 2>&1 | tee -a storage/logs/deploy.log

# 5. Clear and rebuild caches
php artisan config:cache 2>&1 | tee -a storage/logs/deploy.log
php artisan route:cache 2>&1 | tee -a storage/logs/deploy.log
php artisan view:cache 2>&1 | tee -a storage/logs/deploy.log

# 6. Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "✅ Deployment completed at $(date)" >> storage/logs/deploy.log
