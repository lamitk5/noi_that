FROM php:8.2-fpm-alpine AS php-base

RUN apk add --no-cache bash nginx curl gettext su-exec tini ca-certificates \
    libpng libjpeg-turbo libwebp freetype libzip oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev libzip-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip gd bcmath opcache \
    && apk del .build-deps

WORKDIR /var/www

FROM php-base AS build

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress \
    --no-scripts --no-autoloader

COPY . .

RUN mkdir -p bootstrap/cache storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs storage/app/public \
    && rm -rf public/build public/hot \
    && composer dump-autoload --no-dev --optimize --no-interaction \
    && composer check-platform-reqs --no-dev

FROM node:22-bookworm-slim AS assets

WORKDIR /app
COPY package.json package-lock.json ./
ARG NPM_CACHE_BUST=1
RUN npm ci --no-audit --no-fund \
    && npm install --no-save --no-audit --no-fund \
        @rollup/rollup-linux-x64-gnu@4.63.3 \
        lightningcss-linux-x64-gnu@1.32.0 \
        @tailwindcss/oxide-linux-x64-gnu@4.3.3
COPY vite.config.js ./
COPY resources ./resources
COPY --from=build /var/www/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
    ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

FROM php-base AS production

ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr LOG_LEVEL=info \
    DB_CONNECTION=mysql SESSION_DRIVER=database SESSION_SECURE_COOKIE=true \
    CACHE_STORE=database QUEUE_CONNECTION=sync PORT=10000 RUN_MIGRATIONS=true

COPY --from=build --chown=www-data:www-data /var/www /var/www
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/public/build
# Kept outside /var/www/storage so a Render disk mounted there does not hide the catalog photos.
COPY --from=build --chown=www-data:www-data /var/www/storage/picture /opt/catalog-pictures
COPY docker/nginx.conf /etc/nginx/templates/default.conf.template
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/app-entrypoint

RUN mkdir -p /run/nginx \
    && chmod -R ug+rwX /var/www/storage /var/www/bootstrap/cache

EXPOSE 10000

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl --fail --silent "http://127.0.0.1:${PORT}/up" > /dev/null || exit 1

ENTRYPOINT ["/sbin/tini", "--", "/usr/local/bin/app-entrypoint"]
