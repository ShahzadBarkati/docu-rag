# Production image for docu-rag backend.
# A single container serves the app (nginx + php-fpm) and runs the
# queue worker (supervisord). APP_ROLE=web is the default; setting
# APP_ROLE=worker runs only the queue worker (e.g. an external
# background process on Render/Fly).

# ---- Composer build stage ----
FROM composer:2 AS vendor

WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts --ignore-platform-reqs

# ---- Runtime stage ----
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        poppler-utils \
    && docker-php-ext-install pdo_pgsql zip \
    && docker-php-ext-enable pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Allow large file uploads (up to 100MB docs)
RUN printf 'upload_max_filesize=100M\npost_max_size=110M\nmemory_limit=3072M\nmax_execution_time=300\n' > /usr/local/etc/php/conf.d/upload-limits.ini

WORKDIR /var/www/html

COPY --from=vendor /app/vendor /var/www/html/vendor
COPY backend /var/www/html

COPY backend.docker/nginx.conf /etc/nginx/sites-available/default
COPY backend.docker/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY backend.docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]