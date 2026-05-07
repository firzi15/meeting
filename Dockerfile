FROM php:8.2-apache

# Install PostgreSQL client libraries and PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions for Apache
RUN chown -R www-data:www-data /var/www/html

# Port exposure
EXPOSE 80
