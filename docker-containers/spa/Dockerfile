FROM mhart/alpine-node

WORKDIR /spa

EXPOSE 4200

COPY /www-workspace /spa

RUN yarn

CMD yarn run gulp

CMD yarn run serve