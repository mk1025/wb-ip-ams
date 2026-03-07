#!/bin/sh
set -e

php artisan key:generate --force
php artisan optimize:clear
php artisan config:cache

mkdir -p database
touch database/database.sqlite

php artisan migrate --force

exec php artisan serve --host=0.0.0.0 --port=8000
