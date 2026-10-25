FROM php:8.3-fpm-bookworm AS php-extensions

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libzip-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        mbstring \
        opcache \
        pdo_pgsql \
        zip

FROM php-extensions AS composer-build

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
COPY app ./app
COPY database ./database

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

FROM node:22-bookworm-slim AS assets-build

WORKDIR /app
COPY package.json ./
RUN npm install --no-audit --no-fund
COPY resources ./resources
COPY vite.config.js ./
COPY public ./public
RUN npm run build

FROM php:8.3-fpm-bookworm AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    DB_CONNECTION=pgsql \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    SESSION_SECURE_COOKIE=true

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        gettext-base \
        libicu72 \
        libonig5 \
        libpq5 \
        libzip4 \
        nginx \
        supervisor \
    && rm -rf /var/lib/apt/lists/*

COPY --from=php-extensions /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=php-extensions /usr/local/etc/php/conf.d/docker-php-ext-*.ini /usr/local/etc/php/conf.d/
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-kasirku.ini

WORKDIR /var/www/html
COPY . .
COPY --from=composer-build /var/www/html/vendor ./vendor
COPY --from=assets-build /app/public/build ./public/build

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf.template /etc/nginx/templates/default.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/kasirku-entrypoint

RUN mkdir -p \
        /var/www/html/storage/framework/cache/data \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
        /var/www/html/bootstrap/cache \
        /tmp/nginx-client-body \
        /tmp/nginx-fastcgi \
        /tmp/nginx-proxy \
        /tmp/nginx-scgi \
        /tmp/nginx-uwsgi \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /tmp/nginx-client-body /tmp/nginx-fastcgi /tmp/nginx-proxy /tmp/nginx-scgi /tmp/nginx-uwsgi \
    && chmod +x /usr/local/bin/kasirku-entrypoint

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/kasirku-entrypoint"]
