FROM mhart/alpine-node:10

WORKDIR /var/www/spa

EXPOSE 4200

COPY . /var/www/spa

RUN npm i -y

RUN npm rebuild node-sass

RUN npm run gulp