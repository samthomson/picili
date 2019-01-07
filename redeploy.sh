#!bin/bash
eval $(docker-machine env picili)
docker-compose -f docker-compose.prod.yml build --no-cache
docker-compose -f docker-compose.prod.yml up