#!bin/bash
docker-compose -f docker-compose.prod.yml run -d --entrypoint="bash -c 'cd /var/www/auto-scaler && npm run forever'" workspace