# deploy

## initial remote machine creation

- get and set digital ocean token into env `export DO_TOKEN="INSERT_TOKEN_HERE"`

- create remote machine: `docker-machine create --driver=digitalocean --digitalocean-access-token=$DO_TOKEN --digitalocean-size=2gb --digitalocean-region=sgp1 picili`
- switch 'into' it: `eval $(docker-machine env picili-test-instance)`
- build: `docker-compose build -f docker-compose.prod.yml`
- seed: `docker-compose run workspace bash` and then `bash seed.sh`
- run `docker-compose up -f docker-compose.prod.yml`



docker-compose -f docker-compose.prod.yml build --no-cache spa