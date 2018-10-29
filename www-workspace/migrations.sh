#!/bin/bash
set -eu -o pipefail

# DATABASE_NAME
# DATABASE_NAME_TESTING
# DB_HOST
# DB_PASSWORD

echo "CREATE DATABASE IF NOT EXISTS ${DATABASE_NAME};CREATE DATABASE IF NOT EXISTS ${DATABASE_NAME_TESTING};" | mysql -h ${DB_HOST} -p${DB_PASSWORD}

cd /var/www/user-api-laravel
php artisan migrate --force

cd /var/www/auto
php artisan migrate --force
php artisan migrate --force --path="../picili-shared/Migrations"
