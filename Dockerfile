FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install mysqli pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/apache/security.conf /etc/apache2/conf-available/security.conf

RUN a2enconf security
