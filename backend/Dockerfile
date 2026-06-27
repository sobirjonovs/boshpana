# Boshpana.ai backend — Laravel 13 / PHP 8.4
FROM php:8.4-cli

# System deps + PHP extensions (intl, zip, gd, bcmath, pdo_sqlite for Filament + app)
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions intl zip gd bcmath pdo_sqlite pdo_mysql exif pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-progress --prefer-dist

COPY . .
RUN composer dump-autoload --optimize \
    && touch database/database.sqlite

EXPOSE 8000

# Migrate+seed on boot, then serve. (Idempotent for the demo.)
CMD php artisan migrate --force --seed --no-interaction \
    && php artisan serve --host=0.0.0.0 --port=8000
