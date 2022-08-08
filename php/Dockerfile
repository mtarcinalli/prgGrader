FROM php:8.0-apache
RUN apt-get update && apt-get install -y libpq-dev unzip build-essential cxxtest default-jdk bc && docker-php-ext-install pdo pdo_pgsql
RUN mkdir /var/www/uploads
RUN chown www-data /var/www/uploads
RUN chmod o+w /var/www/uploads