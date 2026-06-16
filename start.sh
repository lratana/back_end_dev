#!/bin/bash
set -e

chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

composer install

php artisan key:generate --force
php artisan migrate --force

php artisan optimize:clear
php artisan config:cache
php artisan storage:link

php artisan queue:retry all

# =========================
# START SERVICES (CORRECT)
# =========================

apache2-foreground &

php artisan reverb:start &

# 🔥 RUN SCHEDULER IN BACKGROUND (IMPORTANT FIX)
php artisan schedule:work &

# queue worker
php artisan queue:work --tries=3 --timeout=600