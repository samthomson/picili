#!bin/bash

git pull

docker-compose -f docker-compose.prod.yml down

docker-compose -f docker-compose.prod.yml up -d

bash ./deploy-scripts/start-auto-scaler.sh