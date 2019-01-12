
CREATE:
- docker-machine create
- `docker-machine ssh picili "git clone https://github.com/samthomson/picili.git"`
- `docker-machine scp .env.prod picili:/picili/.env`
- `docker-machine ssh picili`
- `apt install docker-compose`
- `cd /picili`
- `docker-compose up -d`
- run setup script 
	- ssh in to VPS : `docker-machine ssh picili`
	- bash in to container: `cd /picili && docker-compose run workspace bash`
	- run script: `bash ./initial-setup.sh`

- docker-compose up
- run initial-setup script (~./www-workspace/initial-setup.sh)

UPDATE:
- ssh in
- git pull
- docker-compose down
- docker-compose up