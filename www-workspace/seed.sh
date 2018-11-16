#!/bin/bash

# docker-compose run workspace bash

cd /var/www/user-api-laravel && composer install
cd /var/www/auto && composer install
cd /var/www && ./migrations.sh
cd /var/www/auto && php artisan db:seed --class=FolderSeeder
cd /var/www/auto && php artisan elastic-create
cd /var/www/spa && npm i && ng build && npm run gulp && npm run gulp dist