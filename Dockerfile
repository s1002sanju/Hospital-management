FROM php:8.2-apache

# PostgreSQL (Supabase) + MySQL (local dev) drivers. curl/fileinfo are already built into this image.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Serve ONLY /public (config, includes, database stay unreachable from the web)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && echo 'ServerTokens Prod' > /etc/apache2/conf-available/zz-security-extra.conf \
    && a2enconf servername zz-security-extra

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
# strip Windows line endings in case the repo was checked out on Windows
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html
COPY . /var/www/html
RUN mkdir -p storage && chown -R www-data:www-data storage

EXPOSE 10000
CMD ["entrypoint.sh"]
