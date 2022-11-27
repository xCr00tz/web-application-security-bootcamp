FROM php:7.1-apache

ENV APACHE_DOCUMENT_ROOT /usr/src/deeplink

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY index.php /usr/src/deeplink/
COPY vuln.php /usr/src/deeplink/