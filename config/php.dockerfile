# Use an official PHP image as the base
FROM php:8.2-fpm

# Install necessary packages
RUN apt-get update && apt-get install -y \
    gpiod \
    libgpiod2 \
    libgpiod-dev \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Set ownership and permissions for application directory
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Add entrypoint script to handle GPIO permissions
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Add www-data to gpio group for GPIO access
RUN groupadd -r gpio && usermod -a -G gpio www-data

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Set the entrypoint to handle GPIO permissions and start PHP-FPM
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
