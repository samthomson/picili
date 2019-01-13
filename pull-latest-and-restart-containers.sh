#!bin/bash

git pull

docker-compose -f docker-compose.prod.yml down

docker-compose -f docker-compose.prod.yml up -d

exec bash ./deploy-scripts/start-auto-scaler.sh