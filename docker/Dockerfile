# Use the official PHP image
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /workspace

# Configure PHP extensions if needed
RUN docker-php-ext-configure pcntl --enable-pcntl

# Install PHP extensions if needed
RUN docker-php-ext-install pdo pdo_mysql pcntl


# Install xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY ./xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Expose the port for the PHP server
EXPOSE 8000