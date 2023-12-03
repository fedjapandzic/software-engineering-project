# Use an official PHP runtime as a parent image
FROM php:8.1.0-apache

# Set the working directory in the container
WORKDIR /var/www/html

# Copy the current directory contents into the container
COPY . /var/www/html

# Install PostgreSQL extension
RUN apt-get update \
    && apt-get install -y \
        libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-enable pdo_pgsql

# Enable apache modules
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install any dependencies your PHP application needs
# For example, if you are using Composer:
# RUN apt-get update && apt-get install -y composer && composer install

# Expose port 80 to the outside world
EXPOSE 80

# Command to run on container start
CMD ["apache2-foreground"]
