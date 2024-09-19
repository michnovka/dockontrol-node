# Use an official PHP image as the base
FROM php:8.3.11-fpm

# Install necessary packages
RUN apt-get update && apt-get install -y \
    gpiod \
    libgpiod-dev \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Set ownership and permissions for the application directory
RUN chown -R www-data:www-data /var/www/html

# Copy entrypoint script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Set the entrypoint to start PHP-FPM
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
