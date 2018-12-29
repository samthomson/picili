#!bin/bash

# pull latest
git pull

# restart containers and services - possibly bad idea. Not sure if all auto processors are acidic enough to handle this gracefully.
# containers: applications, workspace, spa, php-fpm, nginx, mysql, elasticsearch, dejavu, kibana, phpmyadmin


# stop & remove old/current container instances
## do this for each container docker rm $(docker stop $(sudo docker ps -aqf "name=test-name"))

# build new instances
## docker build -t app .

# start them
## docker run -d -p 80:80 --name=test-name app
