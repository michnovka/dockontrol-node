#!/bin/bash

# Generate self-signed SSL certificate with a validity of 10 years
echo "Generating self-signed SSL certificate..."
mkdir -p /etc/nginx/ssl
openssl req -x509 -nodes -days 3650 \
  -subj "/C=US/ST=State/L=City/O=Organization/CN=localhost" \
  -newkey rsa:2048 \
  -keyout /etc/nginx/ssl/server.key \
  -out /etc/nginx/ssl/server.crt

# Start nginx in the foreground
echo "Starting nginx..."
nginx -g 'daemon off;'