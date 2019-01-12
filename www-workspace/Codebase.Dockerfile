FROM tianon/true

# # copy in our code, so as not to rely on a volume in prod
COPY . /var/www

# ensure directories we need are writable
RUN mkdir -p /var/www/user-api-laravel/storage
RUN mkdir -p /var/www/user-api-laravel/bootstrap/cache
RUN mkdir -p /var/www/auto/storage
RUN mkdir -p /var/www/auto/bootstrap/cache

RUN chmod -R o+w /var/www/user-api-laravel/storage
RUN chmod -R o+w /var/www/user-api-laravel/bootstrap/cache
RUN chmod -R o+w /var/www/auto/storage
RUN chmod -R o+w /var/www/auto/bootstrap/cache