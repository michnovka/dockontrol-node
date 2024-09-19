#!/bin/sh

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

# Start PHP-FPM
php-fpm