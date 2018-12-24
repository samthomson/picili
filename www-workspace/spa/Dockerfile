#use node 10 to mitigate gulp issues..
FROM mhart/alpine-node

WORKDIR /spa

EXPOSE 4200

COPY . /spa

RUN yarn

RUN yarn rebuild node-sass

RUN yarn gulp

CMD yarn serve