#!/bin/bash
set -eu -o pipefail

# DB_NAME
# DB_NAME_TESTING
# DB_HOST
# DB_PASSWORD

echo "CREATE DATABASE IF NOT EXISTS ${DB_NAME};CREATE DATABASE IF NOT EXISTS ${DB_NAME_TESTING};" | mysql -h ${DB_HOST} -p${DB_PASSWORD}

cd /var/www/user-api-laravel
php artisan migrate

cd /var/www/auto
php artisan migrate
php artisan migrate --path="../picili-shared/Migrations"
