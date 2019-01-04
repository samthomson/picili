#!bin/bash
docker-compose run workspace bash -c "cd /var/www/user-api-laravel && vendor/bin/phpunit"