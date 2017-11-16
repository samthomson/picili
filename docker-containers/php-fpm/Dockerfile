#
#--------------------------------------------------------------------------
# Image Setup
#--------------------------------------------------------------------------
#
# To edit the 'php-fpm' base Image, visit its repository on Github
#    https://github.com/Laradock/php-fpm
#
# To change its version, see the available Tags on the Docker Hub:
#    https://hub.docker.com/r/laradock/php-fpm/tags/
#
# Note: Base Image name format {image-tag}-{php-version}
#

FROM laradock/php-fpm:1.4-71

MAINTAINER Mahmoud Zalt <mahmoud@zalt.me>

#
#--------------------------------------------------------------------------
# Mandatory Software's Installation
#--------------------------------------------------------------------------
#
# Mandatory Software's such as ("mcrypt", "pdo_mysql", "libssl-dev", ....)
# are installed on the base image 'laradock/php-fpm' image. If you want
# to add more Software's or remove existing one, you need to edit the
# base image (https://github.com/Laradock/php-fpm).
#
ADD ./laravel.ini /usr/local/etc/php/conf.d

# RUN docker-php-ext-install gd && \
#     docker-php-ext-configure gd \
#         --enable-gd-native-ttf \
#         --with-jpeg-dir=/usr/lib \
#         --with-freetype-dir=/usr/include/freetype2 && \
#     docker-php-ext-install gd

RUN apt-get -y update && \
    apt-get install pkg-config libmagickwand-dev -y && \
    pecl install imagick


RUN usermod -u 1000 www-data

WORKDIR /usr/share/nginx/html

CMD ["php-fpm"]

EXPOSE 9000
