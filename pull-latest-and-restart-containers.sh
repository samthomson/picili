#!bin/bash

# pull latest and disregard and local changes (spa builds)
git fetch origin master
git reset --hard FETCH_HEAD
git clean -df
# restart containers
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml up -d