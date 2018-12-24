#use node 10 to mitigate gulp issues..
FROM mhart/alpine-node

WORKDIR /spa

EXPOSE 4200

ADD . /spa

RUN npm i -y

RUN npm rebuild node-sass

RUN npm run gulp

CMD npm run serve