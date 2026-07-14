FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite headers

WORKDIR /var/www/html
COPY . /var/www/html
RUN cp docker/apache.conf /etc/apache2/sites-available/000-default.conf

RUN mkdir -p storage/uploads/cv \
    && chown -R www-data:www-data storage

EXPOSE 80
