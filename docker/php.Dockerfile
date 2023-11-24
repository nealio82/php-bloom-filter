FROM php:8.2-cli-alpine

# PHP environment / Symfony application dependencies
# Add Git and Zip to make `composer install` faster
RUN apk --no-cache update && apk --no-cache add bash git zip libzip-dev

RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip

# xdebug
RUN apk add --update linux-headers
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del -f .build-deps

# Composer
COPY --from=docker.io/composer:latest /usr/bin/composer /usr/bin/

ENTRYPOINT ["tail", "-f", "/dev/null"]