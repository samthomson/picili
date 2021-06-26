#!bin/bash

SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
source $SCRIPTPATH/../.env

# bash/ssh into remote VPS and run update script there.
# docker-machine ssh picili-app "cd /picili/deploy-scripts && bash pull-latest-and-restart.sh"
docker-machine ssh $DO_HOST "cd /picili && bash pull-latest-and-restart-containers.sh"
