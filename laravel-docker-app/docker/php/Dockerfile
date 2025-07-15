FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libsodium-dev \
    zip \
    unzip \
    default-mysql-client

# Configure GD extension
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Install PHP extensions
RUN docker-php-ext-install \
    bcmath \
    exif \
    gd \
    intl \
    mbstring \
    pdo_mysql \
    pcntl \
    sodium \
    zip

# Enable extensions (some may need manual .ini activation)
RUN docker-php-ext-enable \
    opcache \
    sodium

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html