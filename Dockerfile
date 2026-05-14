FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
        libzip-dev \
        zip \
        libxml2-dev \
        libcurl4-openssl-dev \
        libssl-dev \
        cron \
    && docker-php-ext-install \
        pdo_mysql \
        zip \
        xml \
        curl \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html