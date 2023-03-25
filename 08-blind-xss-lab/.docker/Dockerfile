FROM php:5.6-apache
RUN apt-get update -y

RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2enmod rewrite

# Add correct rights for www folder.
RUN chown -R www-data:www-data /var/www/

WORKDIR /var/www/html

EXPOSE 80