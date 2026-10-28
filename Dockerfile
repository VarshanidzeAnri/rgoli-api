# Production image for the Laravel API (php-fpm). Multi-stage: composer deps → slim runtime.
# Build: docker build -t rgoli-api .

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

FROM php:8.4-fpm-alpine AS runtime
RUN apk add --no-cache icu-libs libzip libpng libjpeg-turbo freetype \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath intl gd zip opcache pcntl \
    && apk del .build-deps \
    && { echo 'opcache.enable=1'; echo 'opcache.validate_timestamps=0'; echo 'opcache.memory_consumption=192'; \
         echo 'upload_max_filesize=64M'; echo 'post_max_size=128M'; echo 'expose_php=Off'; } > /usr/local/etc/php/conf.d/rgoli.ini

WORKDIR /var/www/api
COPY --from=vendor --chown=www-data:www-data /app /var/www/api
RUN rm -rf tests .env database/*.sqlite \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
# Config/route/view caches are built at container start (they need runtime env).
CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && php-fpm"]
