
# git pull

# restart containers and services - possibly bad idea. Not sure if all auto processors are acidic enough to handle this gracefully.
docker-machine ssh picili-app "cd test && bash update-app.sh"