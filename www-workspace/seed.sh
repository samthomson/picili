#!/bin/bash

cd /var/www/user-api-laravel && composer install
cd /var/www/auto && composer install
cd /var/www && ./migrations.sh
cd /var/www/auto && php artisan db:seed --class=FolderSeeder --force
cd /var/www/auto && php artisan elastic-create
cd /var/www/spa && npm i && npm run gulp && ng build && npm run gulp dist