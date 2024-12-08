FROM php:8.1-apache-buster

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
    && rm -rf /var/lib/apt/lists/*

    # Additional packages that might be removed
    # libmagickwand-dev \

# Install PHP Extensions
RUN docker-php-ext-install \
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

# Enable apache modules
# RUN a2enmod rewrite headers

# Layering Composer binary
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Add Source Code
COPY ./src /var/www/html

ENV APP_ENV=prod

# Install Composer Dependencies
RUN composer install --no-dev --optimize-autoloader

# Cleanup
RUN rm -rf /usr/src/*
