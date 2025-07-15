FROM php:8.2-fpm

# Install system dependencies and development tools
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
    default-mysql-client \
    curl \
    gnupg \
    git \
    vim \
    nano \
    htop \
    procps \
    net-tools

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

# Install Node.js 20.x LTS and npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g npm@latest

# Install additional global npm packages for development
RUN npm install -g \
    @vue/devtools \
    vite \
    typescript

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Set working directory
WORKDIR /var/www/html

# Create a non-root user for development (optional but recommended)
RUN groupadd -g 1000 laravel && \
    useradd -u 1000 -ms /bin/bash -g laravel laravel

# Ensure www-data can write to storage and cache
RUN usermod -a -G www-data laravel

# Expose port 5173 for Vite dev server
EXPOSE 5173