FROM php:8.4-fpm-alpine

# System deps + PHP extensions needed by Laravel + PostgreSQL
RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libzip-dev \
        postgresql-dev \
        oniguruma-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        intl \
        zip \
        bcmath \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Reasonable FPM/opcache defaults for dev
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/questmap.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-questmap.conf

EXPOSE 9000
# -R allows the pool to run as root (dev bind-mount convenience; see www.conf)
CMD ["php-fpm", "-R"]
