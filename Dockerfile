FROM php:8.1-apache

# Installing system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo_mysql zip

# Copying project files
COPY . /var/www/html

# Installing Composer dependencies
RUN apt-get install -y composer
RUN composer install --no-dev --optimize-autoloader

# Setting permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Optimizing Laravel configuration
RUN php artisan config:cache
RUN  php artisan route:cache
RUN php artisan view:cache

# Exposing port
EXPOSE 8080

# Starting Apache
CMD ["apache2-foreground"]