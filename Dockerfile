FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libsqlite3-dev libonig-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo_pgsql pdo_sqlite mbstring curl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/cloudcv-entrypoint

RUN chmod +x /usr/local/bin/cloudcv-entrypoint \
    && mkdir -p /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/storage

ENV PORT=10000
EXPOSE 10000
ENTRYPOINT ["cloudcv-entrypoint"]
CMD ["apache2-foreground"]
