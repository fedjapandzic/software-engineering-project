# Use an official PHP runtime as a parent image
FROM php:8.1.0-apache

# Set the working directory in the container
WORKDIR /var/www/html

# Copy the current directory contents into the container
COPY . /var/www/html

# Install MySQLi extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

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
