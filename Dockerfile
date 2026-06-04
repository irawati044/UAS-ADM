FROM php:8.2-apache

# Install PDO MySQL extension required for database connection
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite
