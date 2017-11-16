# Picili

## phpmyadmin container

http://localhost:8080
- host: mysql
- user: root
- password: password
(doesn't follow env values at all..)

## kibana

http://localhost:5601/

console browser: http://localhost:5601/app/kibana#/dev_tools/console?_g=()

# but on sequel client
- host: 127.0.0.1
- user: root
- password: password

install each php projects composer dependencies

```
docker-compose run workspace bash

CMD cd /var/www/user-api-laravel && composer install
CMD cd /var/www/auto && composer install
./migrate.sh
```

Do everything dev related in the workspace container:
`docker-compose run workspace bash`


### migrations

#### auto
```
php artisan migrate
php artisan migrate --path="../picili-shared/Migrations"
```
### user-api-laravel
```
php artisan migrate
```

### seeders

- seeder to create folders: `cd /var/www/auto && php artisan db:seed --class=FolderSeeder`

## run all tests

/var/www/user-api-laravel/vendor/bin/phpunit
/var/www/auto/vendor/bin/phpunit

# run a specific test

vendor/bin/phpunit --filter testUpdateDropboxFilesource Tests/Feature/BlackboxTest

# use site

Site runs at http://localhost
- it's the SPA backed with the API (the spa is just dumped into the api projects public folder)

## commands

- delete elastic: `cd /var/www/auto && php artisan elastic-delete`
- create elastic index: `cd /var/www/auto && php artisan elastic-create`
- re-index: `cd /var/www/auto && php artisan index-all`

- re-create: `cd /var/www/auto && php artisan elastic-delete && php artisan elastic-create`
- all (re-create and re-index): `cd /var/www/auto && php artisan elastic-delete && php artisan elastic-create && php artisan index-all`