FROM mhart/alpine-node

WORKDIR /spa

EXPOSE 4200

COPY . /spa

RUN yarn

RUN yarn run gulp

CMD yarn run serve