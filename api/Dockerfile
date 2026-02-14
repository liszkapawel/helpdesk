FROM php:8.2-fpm-alpine

RUN apk add --no-cache icu-dev \
    && docker-php-ext-install pdo_mysql intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize
