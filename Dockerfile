FROM php:8.2-apache

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y libpng-dev libzip-dev unzip default-mysql-client libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql gd zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html

RUN cp /var/www/html/public/.htaccess /var/www/html/.htaccess 2>/dev/null || true

RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's#/var/www/html#/var/www/html/public#' /etc/apache2/apache2.conf \
    && printf '\n<Directory "/var/www/html/public">\n    AllowOverride All\n    Require all granted\n</Directory>\n' >> /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2-foreground"]
