#!bin/bash

# read env var to get host details
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
source $SCRIPTPATH/../.env

# check env vars
echo "we'll create a VPS called $DO_HOST"
echo "in region: $DO_DROPLET_REGION"
echo "of size: $DO_DROPLET_SIZE"

# create remote machine
docker-machine create --driver=digitalocean --digitalocean-access-token=$DO_TOKEN --digitalocean-size=$DO_DROPLET_SIZE --digitalocean-region=$DO_DROPLET_REGION $DO_HOST


# enable swap
docker-machine scp $SCRIPTPATH/enableswap.sh $DO_HOST:/enable-swap-locally.sh
docker-machine ssh $DO_HOST "bash /enable-swap-locally.sh"


# deploy code 
docker-machine ssh $DO_HOST "git clone https://github.com/samthomson/picili.git /picili"

# push up env var
docker-machine scp .env.prod $DO_HOST:/picili/.env

# bash in to remote machine and instal deps and setup project
docker-machine ssh $DO_HOST "apt install docker-compose -y"

cd /picili

# setup script
docker-compose -f docker-compose.prod.yml run workspace bash "./prod-initial-setup.sh"

# start services 
docker-compose -f docker-compose.prod.yml up -d
exit

# display machine ip

docker-machine ip $DO_HOST