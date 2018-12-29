# deploy

## initial remote machine creation

export DO_TOKEN="INSERT_TOKEN_HERE"

create remote machine: `docker-machine create --driver=digitalocean --digitalocean-access-token=$DO_TOKEN --digitalocean-size=2gb picili-app`
- `docker-machine ssh picili-app`
- `git clone https://github.com/samthomson/picili.git picili-repo`
- [follow update workflow]

## update workflow

- `docker-machine ssh picili-app`
- `cd picili-repo`
- `bash update-app.sh`




- all a bad idea as how would I pass in env vars, too much hassle. Will try and get docker-compose approach workind in seperate repo.