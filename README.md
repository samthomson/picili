# Picili

![build status](https://circleci.com/gh/samthomson/picili.svg?&style=shield)

picili is an automated image search engine / browser. It syncronises with a chosen folder on your dropbox, and analyses all pictures there. Staying upto date with any pictures you add or remove. You can then search through all your pictures more easily, and browse them with a map and calendar interface.

1. [How it works](#10-how-it-works)
2. [Set up](#20-set-up-picili-locally)


## 1.0 How it works

- polls dropbox every x minutes, gets a list of files.
- compares to all files it has identified so far.
- adds any new ones to a local list and quees them to be imported, and removes any now deleted files.
- queued files are each downloaded locally, processed, and then deleted locally
- processing consists of
    - create thumbnails
    - extract exif information
    - determine colours
    - send to subject recognition API
    - if geo exif data
        - geocode - get localtion information (via external API)
        - get altitude (via external API)

## 2.0 Set up picili locally

### phpmyadmin container

http://localhost:8080
- host: mysql
- user: root
- password: password
(doesn't follow env values at all..)

#### connect to db via a sequel client
- host: 127.0.0.1
- user: root
- password: password

### kibana

http://localhost:5601/

console browser: http://localhost:5601/app/kibana#/dev_tools/console?_g=()


### set up

- `docker up` to build
- bash into *workspace* container `docker-compose run workspace bash`
- install each php projects composer dependencies

```
docker-compose run workspace bash

CMD cd /var/www/user-api-laravel && composer install
CMD cd /var/www/auto && composer install
./migrate.sh
```

Do everything dev related in the workspace container:
`docker-compose run workspace bash`



#### seeders

- seeder to create folders: `cd /var/www/auto && php artisan db:seed --class=FolderSeeder`

### run all tests

/var/www/user-api-laravel/vendor/bin/phpunit
/var/www/auto/vendor/bin/phpunit

## run a specific test

vendor/bin/phpunit --filter testUpdateDropboxFilesource tests/Feature/BlackboxTest

## use site

Site runs at http://localhost
- it's the SPA backed with the API (the spa is just dumped into the api projects public folder)

### commands

(to be run from the workspace container)

- delete elastic: `cd /var/www/auto && php artisan elastic-delete`
- create elastic index: `cd /var/www/auto && php artisan elastic-create`
- re-index: `cd /var/www/auto && php artisan index-all`

- re-create: `cd /var/www/auto && php artisan elastic-delete && php artisan elastic-create`
- all (re-create and re-index): `cd /var/www/auto && php artisan elastic-delete && php artisan elastic-create && php artisan index-all`