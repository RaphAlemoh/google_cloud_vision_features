# Dockerfile
FROM composer:2.3.10 as build
WORKDIR /app
COPY . /app
RUN composer install && composer dumpautoload
RUN php artisan optimize:clear

FROM php:8.1.0RC5-apache-buster
RUN apt update && apt install -y zlib1g-dev libpng-dev libwebp-dev libjpeg-dev libfreetype6-dev && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype
RUN docker-php-ext-install gd

EXPOSE 80
COPY --from=build /app /var/www/
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN chmod 777 -R /var/www/storage/ && \
  echo "Listen 8080">>/etc/apache2/ports.conf && \
  chown -R www-data:www-data /var/www/ && \
  a2enmod rewrite

