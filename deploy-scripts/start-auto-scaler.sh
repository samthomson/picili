#!bin/bash
docker exec -it $(docker ps -qf name=picili_workspace) bash -c "cd /var/www/auto-scaler && npm run forever && exit"