#!/bin/sh

# Ensure the GPIO permissions
chmod 666 /dev/gpiochip*

# Start PHP-FPM
php-fpm
