#!bin/bash
docker-compose -f docker-compose.prod.yml run -d --entrypoint="bash -c 'cd /var/www/auto && php artisan elastic-delete && php artisan elastic-create && php artisan index-all'" workspace