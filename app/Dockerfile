# ================================
# Stage 1: PHP Dependencies
# ================================
FROM composer:2.7 AS composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

# ================================
# Stage 2: Production Runtime
# ================================
FROM php:8.3-fpm-alpine AS runtime

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    opcache

# PHP config
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Nginx config
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Non-root user
RUN addgroup -g 1001 -S laravel && adduser -u 1001 -S laravel -G laravel

# Fix nginx dirs for non-root
RUN mkdir -p /var/lib/nginx/logs /var/lib/nginx/tmp \
    && chown -R laravel:laravel /var/lib/nginx \
    && chown -R laravel:laravel /var/log/nginx \
    && chown -R laravel:laravel /etc/nginx/http.d \
    && chown -R laravel:laravel /tmp

WORKDIR /var/www/html

# Copy app files
COPY --chown=laravel:laravel . .
COPY --from=composer --chown=laravel:laravel /app/vendor ./vendor

# Set permissions
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions \
    storage/framework/views bootstrap/cache \
    && chown -R laravel:laravel storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER laravel

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
