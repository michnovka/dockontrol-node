FROM php:8.3.11-fpm

# Install necessary packages
RUN apt update && apt install -y \
    gpiod \
    libgpiod-dev \
    netcat-openbsd \
    wireguard-tools \
    iproute2 \
    procps \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy scripts
COPY scripts/ /scripts/

# Copy custom PHP-FPM pool configuration
COPY config/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf

# Remove the override docker conf that makes it listen on 9000
RUN rm /usr/local/etc/php-fpm.d/zz-docker.conf

# Set ownership and permissions
RUN chown -R www-data:www-data /var/www/html
RUN mkdir -p /var/run/php
RUN chown -R www-data:www-data /var/run/php

# Copy entrypoint script
COPY docker/php-entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set the entrypoint to start PHP-FPM
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
