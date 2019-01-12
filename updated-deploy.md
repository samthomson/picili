
CREATE:
- docker-machine create
- `docker-machine ssh picili "git clone https://github.com/samthomson/picili.git"`
- `docker-machine scp .env.prod picili:/picili/.env`
- `docker-machine ssh picili`
- `apt install docker-compose`
- `cd /picili`
- setup script: `docker-compose -f docker-compose.prod.yml run workspace bash` and then `bash ./initial-setup.sh && exit`
- start services `docker-compose -f docker-compose.prod.yml up -d`
- start auto-scaler: `docker-compose -f docker-compose.prod.yml run workspace bash` again and then `cd /var/www/auto-scaler && npm run forever && exit`

- docker-compose up
- run initial-setup script (~./www-workspace/initial-setup.sh)

UPDATE:
- ssh in `docker-machine ssh picili`
- git pull `cd /picili && git pull`
- docker-compose down: `docker-compose -f docker-compose.prod.yml down`
- docker-compose up: `docker-compose -f docker-compose.prod.yml -d up`

*If rebuilding SPA, must ssh in, bash in to workspace, and run `cd /var/www/spa && npm run dist-prod`

Start auto-scaler:
- setup script: `bash ~/deploy-scripts/start-auto-scaler.sh`
