#!/bin/bash

cd /var/www/user-api-laravel && composer install
cd /var/www/auto && composer install
cd /var/www && ./migrations.sh
cd /var/www/auto && php artisan db:seed --class=FolderSeeder --force
cd /var/www/auto && php artisan elastic-create
cd /var/www/spa && npm i -y && npm run dist-prod
# npm run dist-prod makes it work but isn't the right solution it should build and serve the site, not build and serve staticly