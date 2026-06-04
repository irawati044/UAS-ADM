FROM php:8.2-apache

# 1. Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# 2. Enable Apache mod_rewrite
RUN a2enmod rewrite

# 3. SALIN SEMUA FILE WEBSITE DARI EC2 KE DALAM CONTAINER DOCKER
COPY . /var/www/html/

# 4. Beri izin akses (permission) agar Apache bisa membaca file & menulis sessions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html