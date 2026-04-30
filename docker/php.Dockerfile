FROM php:8.3-fpm-alpine

# pdo_pgsql permet à PHP de parler à PostgreSQL via PDO.
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Nginx et PHP utilisent ce chemin commun dans les conteneurs.
WORKDIR /var/www/html

COPY . /var/www/html

# Donne les droits au processus PHP-FPM.
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
