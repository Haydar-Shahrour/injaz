FROM php:8.2-apache

# Enable Apache mod_rewrite for friendly URLs if needed in the future
RUN a2enmod rewrite

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Copy all project files into the Apache document root
COPY . /var/www/html/

# Expose port 80
EXPOSE 80
