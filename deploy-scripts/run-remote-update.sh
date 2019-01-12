#!bin/bash

# bash/ssh into remote VPS and run update script there.
# docker-machine ssh picili-app "cd /picili/deploy-scripts && bash pull-latest-and-restart.sh"
docker-machine ssh picili "cd /picili && bash pull-latest-and-restart-containers.sh"
