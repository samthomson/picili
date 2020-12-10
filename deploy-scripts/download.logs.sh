#!bin/bash

# this script should be run from project root
# -r means recursive (to get subfolders/files too), and -d means delta (make a local/remote comparison and download only the difference)

# load in env vars
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
source $SCRIPTPATH/../.env

echo "will pull logs from docker-machine host: $DO_HOST"

docker-machine scp -r -d $DO_HOST:/picili/www-workspace/user-api-laravel/storage/logs $SCRIPTPATH/../serverlogs/api
docker-machine scp -r -d $DO_HOST:/picili/www-workspace/auto/storage/logs $SCRIPTPATH/../serverlogs/auto
