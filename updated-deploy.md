
CREATE:

- get and set digital ocean token into env `export DO_TOKEN="INSERT_TOKEN_HERE"`

- create remote machine: `docker-machine create --driver=digitalocean --digitalocean-access-token=$DO_TOKEN --digitalocean-size=2gb --digitalocean-region=sgp1 picili`
- `docker-machine ssh picili "git clone https://github.com/samthomson/picili.git"`
- `docker-machine scp .env.prod picili:/picili/.env`
- `docker-machine ssh picili`
- `apt install docker-compose`
- `cd /picili`
- setup script: `docker-compose -f docker-compose.prod.yml run workspace bash` and then `bash ./initial-setup.sh && exit`
- start services `docker-compose -f docker-compose.prod.yml up -d`
- start auto-scaler: `bash ~/deploy-scripts/start-auto-scaler.sh`

- docker-compose up
- run initial-setup script (~./www-workspace/initial-setup.sh)

UPDATE:
- ssh in `docker-machine ssh picili`
- git pull `cd /picili && git pull`
- docker-compose down: `docker-compose -f docker-compose.prod.yml down`
- docker-compose up: `docker-compose -f docker-compose.prod.yml -d up`
- restart auto-scaler: `bash ~/deploy-scripts/start-auto-scaler.sh`

*If rebuilding SPA, run: `docker-compose -f docker-compose.prod.yml run -d --entrypoint="bash -c 'cd /var/www/spa && npm run dist-prod'" workspace`
