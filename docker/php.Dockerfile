FROM php:8.3.14-fpm

# Install necessary packages
RUN apt update && apt install -y \
    gpiod \
    libgpiod-dev \
    netcat-openbsd \
    procps \
    && rm -rf /var/lib/apt/lists/*

# Install ext-sockets (for michnovka/openwebnet-php)
RUN docker-php-ext-install sockets

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy scripts
COPY scripts/ /scripts/

# Set ownership and permissions
RUN chown -R www-data:www-data /var/www/html

# Copy entrypoint script
COPY docker/php-entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set the entrypoint to start PHP-FPM
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
