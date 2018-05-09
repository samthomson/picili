# Picili

![build status](https://circleci.com/gh/samthomson/picili.svg?&style=shield)

picili is an automated image search engine / browser. It syncronises with a chosen folder on your dropbox and analyses all pictures there. Staying upto date with any pictures you add or remove. You can then search through all your pictures more easily, and browse them with a map and calendar interface.

![build status](https://picili.com/images/map_screen.jpeg)

1. [How it works](#10-how-it-works)
2. [Set up](#20-set-up-picili-locally)
3. [Working on picili](#30-working-on-picili)
4. [Deploying](#40-deploying)

## 1.0 How it works

- user registers and connects their dropbox account through OAuth
- user then enters a folder on their dropbox where they store their pictures
- picili polls dropbox every x minutes, getting a list of files.
- compares to all files it has identified so far.
- adds any new ones to a local list and quees them to be imported, and removes any now deleted files.
- queued files are each downloaded locally, processed, and then deleted locally
- processing consists of
    - creating thumbnails
    - extracting exif information
    - determining colours
    - sending the picture to a subject recognition API
    - if the picture contains geo exif data
        - geocoding: get localtion information (via external API)
        - altitude encoding (via an external API)

Main parts:
- SPA: the front end is a Angular 2 single page application
- API: a PHP API wrote in laravel which the SPA calls
- Auto: A seperate project running in the background which is also a Laravel PHP project, this does all the synchronizing and auto tagging.
- Auto-Scaler: A small node project which scales up instances of the Auto project based on demand

Techs:
- SPA: JS Typescript / Angular 2 / SASS
- Auto-Scaler: Nodes JS
- API / Auto: PHP / Laravel

Data: MySQL and Elasticsearch

## 2.0 Set up picili locally

Picili is completely dockerized.

- `cd` into the root folder and run `docker-compose up -d` to build
- then bash into *workspace* container: `docker-compose run workspace bash`
- then from within that container run these commands to install dependencies and seed the database with required tables
 - `cd /var/www/user-api-laravel && composer install`
 - `cd /var/www/auto && composer install`
 - `cd /var/www && ./migrations.sh`
- seeder to create folders: `cd /var/www/auto && php artisan db:seed --class=FolderSeeder`
- create elastic mapping: `cd /var/www/auto && php artisan elastic-create`
- picili is now ready to run and should be accesable from `http://localhost`

Click 'login' and then register to begin.

To start the auto processor(s): `cd /var/www/auto-scaler && npm start`

## 3.0 Working on picili

Do everything dev related in the workspace container: `docker-compose run workspace bash`

### run tests

API tests: `cd /var/www/user-api-laravel && vendor/bin/phpunit`
Auto tests: `cd /var/www/auto && vendor/bin/phpunit`

## run a specific test

`cd /var/www/user-api-laravel && vendor/bin/phpunit --filter testUpdateDropboxFilesource tests/Feature/BlackboxTest`

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

### containers / services

#### phpmyadmin container

http://localhost:8080
- host: mysql
- user: root
- password: password
(doesn't follow env values at all..)

##### connect to db via a sequel client
- host: 127.0.0.1
- user: root
- password: password

#### kibana

http://localhost:5601/

console browser: http://localhost:5601/app/kibana#/dev_tools/console?_g=()


### Working on the SPA

The quickest way to work on the SPA is to first install its dependencies with `npm i` or `yarn`, and then run `ng serve`. `ng serve` is and angular-cli command which builds the SPA and serves it to `http://localhost:4200`, it also watches the SPA files and rebuilds/serves the application when it detects changes.

`ng build`
`ng serve`

If you plan on editing sass files, also run `npm run gulp-watch`.

If you want to 'publish' the SPA into the root of the main application (API), so you can test end to end via `http://localhost` then you can run a gulp task (`gulp dist`) as defined in gulpfile.js in the SPA root after running `ng build`. These have been combined into one package script, so you can just run `npm run dist`.

You need to use gulp 4, as installed as a dependency. To specifically run the local gulp and not global (which may not be version 4), so the command like this: `./node_modules/.bin/gulp dist`. 


## 4.0 Deploying

### 4.1 Setup and first deploy

- set up a vps somewhere (tested on: ubuntu 17.10 x64 2gb ram 40gb ssd)
- connect to it from the terminal `root@ipaddress` and then enter your root password and accept the machines fingerprint
- create a new user to replace the root user:  https://www.digitalocean.com/community/tutorials/initial-server-setup-with-ubuntu-16-04
- install nginx, php, mysql
- install elasticsearch. follow part of this guide: https://www.vultr.com/docs/how-to-install-and-configure-elastic-stack-elasticsearch-logstash-and-kibana-on-ubuntu-17-04 and then set heapsize to half of ram: /etc/elasticsearch/jvm.options
- install elastic search and set it to run on startup https://www.elastic.co/guide/en/elasticsearch/reference/5.6/_installation.html and https://www.digitalocean.com/community/tutorials/how-to-set-up-a-production-elasticsearch-cluster-on-ubuntu-14-04
- update memory for elasticsearch jvm.options file to half of all ram available (tested at 1gb)
- before uploading files, change ownership of /var/www dir to new user `sudo chown newuser:newuser /var/www/`
- change ownership of nginx sites available folder: `sudo chown -R newuser:newuser /etc/nginx/sites-available`
- upload nging conf file (default) to /etc/nginx/sites-available
- restart nginx `sudo service nginx restart`
- change owning group `sudo chgrp -R www-data /var/www/picili/user-api-laravel && sudo chgrp -R www-data /var/www/picili/auto`
- give write permission to storage folder: `sudo chmod -R 775 /var/www/picili/user-api-laravel/storage && sudo chmod -R 775 /var/www/picili/auto/storage`
- create elastic indexes: `cd /var/www/picili/auto && php artisan elastic-create`

- create and upload env files to api and auto projects
- set up db
- - connect to mysql: https://www.digitalocean.com/community/tutorials/how-to-connect-to-a-mysql-server-remotely-with-mysql-workbench
- - create a db, 'picili' with 'utf8' encoding and 'utf8_general_ci' encoding
- - run migrations on server: `cd /var/www/picili/user-api-laravel && php artisan migrate --force` and `cd /var/www/picili/auto && php artisan migrate --force && php artisan migrate --path="../picili-shared/Migrations"`
- build spa for prod
- - edit `/picili/www-workspace/spa/src/environments/environment.prod.ts` and then run, from the spa folder: `ng build --env=prod` and then upload the dist folder to /var/www/picili/user-api-laravel/public

- install curl on server `apt-get install php7.1-curl`
- install gd lib: `sudo apt-get install php7.0-gd`
- install xml lib: `sudo apt-get install php7.1-xml`

- setup autoscaler
- - `sudo apt install npm`
- - `cd /var/www/picili/auto/scaler && npm i`
- - start it running `cd /var/www/picili/auto-scaler && npm start`

- configure swap memory if the vps has low ram: https://www.digitalocean.com/community/tutorials/how-to-add-swap-space-on-ubuntu-16-04

- on the server go to `/var/www/picili/auto-scaler` and run `npm start` to initiate the auto scaler which runs the auto processers. You can also run `npm forever` to keep the process running via the forever package.

- create dropbox app
- secure elastic
- create aws s3 bucket

useful:
(https://stackoverflow.com/questions/28392045/php-fpm-laravel-nginx-ubuntu-permission)

### 4.2 Incremental updates - deploying as you work on picili

The API (PHP) and Auto project (PHP) are straight forward to deploy. Just upload any edited files to the server. Running any migrations if added.

Any changes to the auto-scaler (JS) should also be uploaded. But then you should stop the auto-scaler instance already running and start a new one.

Changes to the front-end SPA (JS) are more complicated as assets must first be built from the spa folder (`www-workspace/spa`) with `yarn dist-prod`.

This will trigger a gulp task to also copy the newly built assets into the public folder of the API (`www-workspace/user-api-laravel/public`). Where they can be copied to the server to/from. Specifically it will build the SPA with a prod config, compared to `yarn dist` which has a local config.