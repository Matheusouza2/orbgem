FROM node:22-bookworm-slim AS node

FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
        nginx \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl mbstring opcache pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=node /usr/local/ /usr/local/
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint
COPY composer.json composer.lock package.json package-lock.json ./

RUN composer install \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts \
    && npm ci \
    && chmod +x /usr/local/bin/docker-entrypoint

EXPOSE 8000 5173

ENTRYPOINT ["docker-entrypoint"]
