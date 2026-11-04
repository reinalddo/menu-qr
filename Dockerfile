# syntax=docker/dockerfile:1

# PHP dependencies are installed in a separate stage, so Composer is not part of the final image.
FROM composer:2.10.2 AS vendor
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --classmap-authoritative \
        --ignore-platform-req=ext-gd

FROM php:8.3.36-apache-trixie

LABEL org.opencontainers.image.source="https://github.com/reinalddo/menu-qr" \
      org.opencontainers.image.title="menu-qr" \
      org.opencontainers.image.description="Self-hosted QR digital menu for restaurants" \
      org.opencontainers.image.licenses="MIT"

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libfreetype-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libzip-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j"$(nproc)" gd pdo_mysql zip exif opcache; \
    rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    a2enmod headers; \
    sed -ri 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' \
        /etc/apache2/conf-available/security.conf

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-menuqr.ini
COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/menuqr-entrypoint
COPY --chmod=0755 docker/init-secrets.sh /usr/local/bin/init-secrets
COPY --from=vendor /build/vendor /var/www/vendor
COPY app/ /var/www/app/

RUN mkdir -p /var/www/data && chown www-data:www-data /var/www/data

ENV MENUQR_DATA_DIR=/var/www/data \
    MENUQR_SECRETS_DIR=/run/menuqr

EXPOSE 80
ENTRYPOINT ["menuqr-entrypoint"]
CMD ["apache2-foreground"]
