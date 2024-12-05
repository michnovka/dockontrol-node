#!/bin/bash

cleanup() {
    echo "Shutting down WireGuard interface wg0"
    wg-quick down wg0
    echo "WireGuard interface wg0 is down"
}

trap 'cleanup' SIGTERM SIGINT

# Set the target GID
TARGET_GID=$(stat -c "%g" ${GPIO_DEVICE})
echo "Fetched GPIO GID: $TARGET_GID"

# Check if a group with the target GID exists
group_info=$(getent group $TARGET_GID)

if [ -z "$group_info" ]; then
  # If no group with the GID exists, create a new group with the specified name and GID
  echo "No group with GID $TARGET_GID found. Creating group 'gpio'."
  groupadd -g $TARGET_GID gpio
  group_name="gpio"
else
  # If the group exists, extract the group name
  group_name=$(echo $group_info | cut -d: -f1)
  echo "Found existing group '$group_name' with GID $TARGET_GID."
fi

# Add the user www-data to the group with GID 993
echo "Adding user 'www-data' to group '$group_name'."
usermod -aG $group_name www-data

echo "Done. User 'www-data' is now a member of the group with GID $TARGET_GID."

# Ensure dependencies are installed
composer install --no-dev --optimize-autoloader || exit 1

# Fetch WireGuard configuration and bring up the interface
php /scripts/fetch_wg_conf.php || exit 1

# Bring down wg0 if it already exists
if ip link show wg0 > /dev/null 2>&1; then
    echo "WireGuard interface wg0 already exists. Bringing it down."
    wg-quick down wg0
fi

# Bring up the WireGuard interface
wg-quick up /etc/wireguard/wg0.conf || exit 1

echo "WireGuard interface is up"

# Run php-fpm in the foreground. tini will handle the signals and call cleanup
exec php-fpm
