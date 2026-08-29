FROM php:8.4-fpm

# system deps
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev poppler-utils \
    && docker-php-ext-install pdo_pgsql zip \
    && docker-php-ext-enable pdo_pgsql

# Allow large file uploads (up to 100MB docs)
RUN printf 'upload_max_filesize=100M\npost_max_size=110M\nmemory_limit=3072M\nmax_execution_time=300\n' > /usr/local/etc/php/conf.d/upload-limits.ini

# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 8000

CMD ["sh", "-c", "composer install --no-interaction && php artisan serve --host=0.0.0.0 --port=8000"]