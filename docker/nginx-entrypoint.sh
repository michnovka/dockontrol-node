#!/bin/sh

# Wait for WireGuard interface
while ! ip link show wg0 > /dev/null 2>&1; do
  echo "Waiting for WireGuard interface wg0..."
  sleep 1
done

# Get WireGuard IP
WG_PEER_IP=$(ip -4 addr show wg0 | grep -oP '(?<=inet\s)\d+(\.\d+){3}')

if [ -z "$WG_PEER_IP" ]; then
  echo "Failed to retrieve WireGuard IP."
  exit 1
fi

echo "WireGuard IP: $WG_PEER_IP"

# Substitute IP in nginx config
#export WG_PEER_IP
#envsubst '${WG_PEER_IP}' < /etc/nginx/conf.d/default.conf.tpl > /etc/nginx/conf.d/default.conf

# Start nginx
nginx -g 'daemon off;'
