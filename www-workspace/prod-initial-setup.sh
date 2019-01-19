#!/bin/bash

# ensure dirs are writeable for laravel
mkdir -p /var/www/user-api-laravel/storage
mkdir -p /var/www/user-api-laravel/bootstrap/cache
mkdir -p /var/www/auto/storage
mkdir -p /var/www/auto/bootstrap/cache

chmod -R o+w /var/www/user-api-laravel/storage
chmod -R o+w /var/www/user-api-laravel/bootstrap/cache
chmod -R o+w /var/www/auto/storage
chmod -R o+w /var/www/auto/bootstrap/cache

# for elastic
sysctl vm.max_map_count=262144

# install deps
cd /var/www/user-api-laravel && composer install
cd /var/www/auto && composer install
# db setup
cd /var/www && ./migrations.sh
# seed data?
cd /var/www/auto && php artisan db:seed --class=FolderSeeder --force
# create elastic structure
cd /var/www/auto && php artisan elastic-create

# build and copy spa into correct folder (gulp dist)
cd /var/www/spa && npm i -y
npm rebuild node-sass
npm run dist-prod

# install auto-scalar deps, start it running?
cd /var/www/auto-scaler && npm i