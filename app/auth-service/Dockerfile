FROM php:8.3-cli-alpine

RUN apk upgrade --no-cache && apk add --no-cache \
    unzip \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        pcntl \
        bcmath \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader

COPY . .
RUN mkdir -p bootstrap/cache \
    && composer dump-autoload --optimize \
    && touch .env \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs \
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["./entrypoint.sh"]
