FROM php:8.1-apache as php-base

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get -y update --fix-missing \
    && apt-get -y --no-install-recommends install \
        nano wget \
        dialog \
        libsqlite3-dev \
        default-mysql-client \
        build-essential \
        git \
        curl \
        zip \
        unzip \
        openssl \
        libcurl4-openssl-dev \
        zlib1g-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install \
        pdo_sqlite \
        mysqli \
        curl \
        zip \
        intl \
        mbstring \
        gettext \
        exif \
        gd

# Install additional PHP Extensions
# RUN pecl install xdebug-3.2.0 \
#     && docker-php-ext-enable xdebug \
#     && pecl install redis-5.3.3 \
#     && docker-php-ext-enable redis \
#     && pecl install imagick \
#     && docker-php-ext-enable imagick

FROM php-base as final

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY ./src/composer.json ./src/composer.lock ./ 

# Copy application code (most frequently changed)
COPY ./src /var/www/html

ENV APP_ENV=prod

RUN composer install --no-dev --optimize-autoloader

# Cleanup
RUN rm -rf /usr/src/*

# Configure apache if needed
# RUN a2enmod rewrite headers