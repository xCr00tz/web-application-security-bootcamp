FROM ubuntu:16.04

ARG AUTH_USER="username"
ARG AUTH_PASS="password"

ENV AUTH_USER=${AUTH_USER}
ENV AUTH_PASS=${AUTH_PASS}

RUN apt-get update && apt-get -y upgrade
RUN apt-get -y install apache2
RUN apt-get -y install php libapache2-mod-php php-mcrypt php-curl

COPY vhost.conf /etc/apache2/sites-available/default-web.conf
RUN htpasswd -b -c /var/www/html/.htpasswd ${AUTH_USER} ${AUTH_PASS}

RUN /etc/init.d/apache2 stop

RUN a2dissite 000-default
RUN a2ensite default-web

COPY www/ /var/www/html/
CMD ["apachectl", "-D", "FOREGROUND"]
EXPOSE 80 443