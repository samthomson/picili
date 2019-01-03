# deploy

locally the SPA and API run on localhost port 80 and 81 respectively. In production they both run on port 80, and are served as the same website. The API serves the SPA which has been copied into its public folder as part of the build process.

## initial remote machine creation

- get and set digital ocean token into env `export DO_TOKEN="INSERT_TOKEN_HERE"`

- create remote machine: `docker-machine create --driver=digitalocean --digitalocean-access-token=$DO_TOKEN --digitalocean-size=2gb --digitalocean-region=sgp1 picili`
- switch 'into' it: `eval $(docker-machine env picili)`
- build: `docker-compose build -f docker-compose.prod.yml`
- seed: `docker-compose run workspace bash` and then `bash seed.sh`
- run `docker-compose up -f docker-compose.prod.yml`

## udpating

Rebuild and deploy SPA container

- `eval $(docker-machine env picili)`
- `docker-compose -f docker-compose.prod.yml build --no-cache spa`
- `docker-compose -f docker-compose.prod.yml up spa`

## other

Bash into a container to see what's going on:
- spa: `docker-compose -f docker-compose.prod.yml run spa sh`
- php-fpm: `docker-compose -f docker-compose.prod.yml run php-fpm bash`