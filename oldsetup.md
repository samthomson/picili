
- set up a vps somewhere (tested on: ubuntu 17.10 x64 2gb ram 40gb ssd)
- connect to it from the terminal `root@ipaddress` and then enter your root password and accept the machines fingerprint
- create a new user to replace the root user: https://www.digitalocean.com/community/tutorials/initial-server-setup-with-ubuntu-16-04
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