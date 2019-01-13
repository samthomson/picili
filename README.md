# Picili

![build status](https://circleci.com/gh/samthomson/picili.svg?&style=shield) [![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

picili is an automated image search engine / browser. It syncronises with a chosen folder on your dropbox and analyses all pictures there. Staying up to date with any pictures you add or remove. You can then search through all your pictures more easily, and browse them with a map and calendar interface.

https://picili.com

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
- adds any new ones to a local list and queues them to be imported, and removes any now deleted files.
- queued files are each downloaded locally, processed, and then deleted locally
- processing consists of
  - creating thumbnails
  - extracting exif information
  - determining colours
  - sending the picture to a subject recognition API
  - if the picture contains geo exif data
    - geocoding: get location information (via external API)
    - altitude encoding: derive elevation in metres from latitude/longitude (via an external API)

Main parts:

- SPA: the front end is an Angular single page application (SPA)
- API: a PHP API made using laravel which the SPA calls
- Auto: A seperate project running in the background which is also a Laravel PHP project, this does all the dropbox synchronizing and file tagging/processing.
- Auto-Scaler: A small node js project which scales up instances of the Auto project based on demand

Techs:

- SPA: JS / Typescript / Angular 7 / SASS / Gulp
- Auto-Scaler: Node JS
- API / Auto: PHP / Laravel / Elasticsearch / MySQL
- *: Docker

## 2.0 Set up picili locally

### platform specific notes

Mac: append the workspace volume with `:cached`. So `- ./www-workspace:/var/www` becomes `- ./www-workspace:/var/www:cached`
Linux: you may need to run `sudo sysctl vm.max_map_count=262144` to ensure elasticsearch can run correctly

### setup

Picili is completely dockerized.

- `cd` into the root folder
- create and configure an env file from the sample `cp .env.sample .env`, being sure to update the following keys:
  - APP_KEY (must be 32 characters)
  - APP_URL (eg http://localhost)
  - DROPBOX_CLIENT_ID (app key)
  - DROPBOX_CLIENT_SECRET (app secret)
  - API_GOOGLE_ELEVATION_KEY
  - API_OPEN_CAGE_KEY
  - API_IMAGGA_KEY
  - API_IMAGGA_SECRET
  - AWS_KEY
  - AWS_SECRET
  - AWS_REGION
- run `docker-compose up -d` to build

The first time you run picili locally, you should generate necessary seed data:

- `docker-compose run workspace` followed by `bash ./seed.sh` in the container

* picili is now ready to run and should be accesable from `http://localhost`

Click 'login' and then register to begin.

You will need to start the auto-scalar, for image processing to happen 'in the background'.

To start the auto processor(s): `cd /var/www/auto-scaler && npm start` (this should be run from within the workspace container)

## 3.0 Working on picili

Do everything dev related in the workspace container: `docker-compose run workspace bash`

### run tests

API tests: `cd /var/www/user-api-laravel && vendor/bin/phpunit`
Auto tests: `cd /var/www/auto && vendor/bin/phpunit`

## run a specific test

`cd /var/www/user-api-laravel && vendor/bin/phpunit --filter testUpdateDropboxFilesource tests/Feature/BlackboxTest`

## use site

Site runs at `http://localhost` (SPA) && `http://localhost:81` (API)

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

#### dejavu

http://localhost:1358

Careful entering the cluster details, the URI should contain the protocol and port along with host:

uri: `http://localhost:9200`
index: `files`

### Working on the SPA

Is run from within a docker container `spa`. The Spa is built and run as standard, so just work on the spa source and it will keep rebuilding automatically.
If you want to enter the container, run `docker-compose run spa sh`

The app is served on `localhost:80` and communicates to the API which runs on `localhost:81` (presuming you've already run `docker-compose up [-d]` to start the 'backend'.

If you plan on editing sass files, also run `npm run gulp-watch` (within the container).


## 4.0 Deploying

locally the SPA and API run on localhost port 80 and 81 respectively. In production they both run on port 80, and are served as the same website. The API serves the SPA which has been copied into its public folder as part of the build process.

### 4.1 Setup and first deploy

- get and set digital ocean token into env `export DO_TOKEN="INSERT_TOKEN_HERE"`

- create remote machine: `docker-machine create --driver=digitalocean --digitalocean-access-token=$DO_TOKEN --digitalocean-size=2gb --digitalocean-region=sgp1 picili`
- `docker-machine ssh picili "git clone https://github.com/samthomson/picili.git"`
- `docker-machine scp .env.prod picili:/picili/.env`
	- ensure `USER_API_URL` and `SPA_URL` is the URI of your site
- `docker-machine ssh picili`
- `apt install docker-compose`
- `cd /picili`
- setup script: `docker-compose -f docker-compose.prod.yml run workspace bash` and then `bash ./initial-setup.sh && exit`
- start services `docker-compose -f docker-compose.prod.yml up -d`
- start auto-scaler: `bash ~/deploy-scripts/start-auto-scaler.sh`

- docker-compose up
- run initial-setup script (~./www-workspace/initial-setup.sh)

Seperately:
- update your dropbox app to have an allowed redirect URI: `https://[YOUR IP/SITE]/oauth/dropbox`
- configure swap memory if the vps has low ram: https://www.digitalocean.com/community/tutorials/how-to-add-swap-space-on-ubuntu-16-04

### 4.2 Incremental updates - deploying as you work on picili

- update remote files, restart images `bash ./depoy-scripts/run-remote-update.sh`
- restart auto-scaler: 
	- `docker-machine ssh picili "docker exec -it $(docker ps -aqf "name=workspace") bash"`
	- `cd /var/www/auto-scaler && npm run forever && exit`

*If rebuilding SPA, run: `docker-compose -f docker-compose.prod.yml run -d --entrypoint="bash -c 'cd /var/www/spa && npm run dist-prod'" workspace`


### 4.3 other

Bash into a container to see what's going on:
- ssh in to server: `docker-machine ssh picili`
- spa: `docker-compose -f docker-compose.prod.yml run spa sh`
- php-fpm: `docker-compose -f docker-compose.prod.yml run php-fpm bash`