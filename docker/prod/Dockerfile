# Production image: built assets + vendor baked in, served by PHP-FPM behind Caddy.
#   docker compose -f compose.prod.yaml up -d --build

FROM node:22-alpine AS assets
WORKDIR /src
COPY package.json package-lock.json .npmrc ./
RUN npm ci
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources resources
COPY public public
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /src
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --no-scripts --optimize

FROM php:8.4-fpm AS app
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libicu-dev libzip-dev libpng-dev libfreetype6-dev libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql intl zip gd bcmath opcache \
    && pecl install redis && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/* \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/prod/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
WORKDIR /var/www/html
COPY --chown=www-data:www-data --from=vendor /src /var/www/html
COPY --chown=www-data:www-data --from=assets /src/public/build public/build
RUN rm -f public/hot && chown -R www-data:www-data storage bootstrap/cache
USER www-data
RUN php artisan package:discover
USER root
COPY docker/prod/entrypoint.sh /usr/local/bin/entrypoint
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

FROM caddy:2-alpine AS web
COPY docker/prod/Caddyfile /etc/caddy/Caddyfile
COPY --from=app /var/www/html/public /var/www/html/public
