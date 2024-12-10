#!/bin/bash

# A script to manage Dockontrol Node operations

# Check if we're running as root
if [ "$EUID" -ne 0 ]; then
  echo "Please run as root"
  exit 1
fi

COMMAND=$1
shift

function check_dependencies() {
  DEPENDENCIES=("wg" "wg-quick" "docker" "git")
  MISSING_DEPS=()
  for dep in "${DEPENDENCIES[@]}"; do
    if ! command -v $dep &> /dev/null; then
      MISSING_DEPS+=($dep)
    fi
  done
  if [ ${#MISSING_DEPS[@]} -ne 0 ]; then
    echo "Missing dependencies: ${MISSING_DEPS[*]}"
    exit 1
  else
    echo "All dependencies are installed."
  fi
}

function fetch_config() {
  # Build the 'php' service (will skip if already built)
  echo "Building the 'php' service if necessary..."
  docker compose build php || exit 1

  # Run the fetch script inside the php container and capture output
  echo "Fetching WireGuard configuration..."
  WG_CONF=$(docker compose run --rm --entrypoint "/usr/local/bin/php" php /scripts/fetch_wg_conf.php)
  if [ $? -ne 0 ]; then
    echo "Failed to fetch WireGuard configuration."
    exit 1
  fi

  # Ensure the /etc/wireguard directory exists
  mkdir -p /etc/wireguard/

  # Save the configuration to /etc/wireguard/wg0.conf
  echo "$WG_CONF" > /etc/wireguard/wg0.conf
  echo "WireGuard configuration saved to /etc/wireguard/wg0.conf"
}

function build() {
  docker compose build || exit 1
}

function check_wg() {
  if ip link show wg0 > /dev/null 2>&1; then
    echo "WireGuard interface wg0 is up."

    # Get the server IP from wg0.conf
    WG_SERVER_IP=$(awk '/Endpoint/ {print $3}' /etc/wireguard/wg0.conf | cut -d':' -f1)

    # If Endpoint is not present or empty, try to get AllowedIPs
    if [ -z "$WG_SERVER_IP" ]; then
      WG_SERVER_IP=$(wg show wg0 peers | xargs -I {} wg show wg0 allowed-ips {} | awk '{print $2}' | cut -d'/' -f1 | head -n1)
    fi

    if [ -z "$WG_SERVER_IP" ]; then
      echo "Failed to determine WireGuard server IP."
      exit 1
    fi

    PING_RESULT=$(ping -c 1 -W 1 "$WG_SERVER_IP")
    if echo "$PING_RESULT" | grep "1 received" > /dev/null; then
      echo "Ping to WireGuard server ($WG_SERVER_IP) successful. WireGuard connection is OK."
    else
      echo "WireGuard interface wg0 is up but ping to server ($WG_SERVER_IP) failed."
    fi
  else
    echo "WireGuard interface wg0 is down."
  fi
}

function start() {
  fetch_config || exit 1

  # Start WireGuard
  if systemctl is-active --quiet wg-quick@wg0; then
    echo "WireGuard interface wg0 is already up. Restarting..."
    systemctl restart wg-quick@wg0 || exit 1
  else
    systemctl start wg-quick@wg0 || exit 1
  fi

  # Wait for WireGuard to be up and connected
  echo -n "Waiting for WireGuard connection"
  ATTEMPTS=0
  MAX_ATTEMPTS=12
  while [ $ATTEMPTS -lt $MAX_ATTEMPTS ]; do
    if ip link show wg0 > /dev/null 2>&1; then
      # Get the server IP from wg0.conf
      WG_SERVER_IP=$(awk '/Endpoint/ {print $3}' /etc/wireguard/wg0.conf | cut -d':' -f1)

      # If Endpoint is not present or empty, try to get AllowedIPs
      if [ -z "$WG_SERVER_IP" ]; then
        WG_SERVER_IP=$(wg show wg0 peers | xargs -I {} wg show wg0 allowed-ips {} | awk '{print $2}' | cut -d'/' -f1 | head -n1)
      fi

      if [ -n "$WG_SERVER_IP" ]; then
        PING_RESULT=$(ping -c 1 -W 1 "$WG_SERVER_IP")
        if echo "$PING_RESULT" | grep "1 received" > /dev/null; then
          echo " WireGuard connection established."
          break
        fi
      else
        echo " Failed to determine WireGuard server IP."
        exit 1
      fi
    fi
    echo -n "."
    sleep 5
    ((ATTEMPTS++))
  done
  if [ $ATTEMPTS -eq $MAX_ATTEMPTS ]; then
    echo " Failed to establish WireGuard connection."
    exit 1
  fi

  # Get the WG0 interface IP address
  NGINX_LISTEN_IP=$(ip -4 addr show wg0 | grep -oP '(?<=inet\s)\d+(\.\d+){3}')
  if [ -z "$NGINX_LISTEN_IP" ]; then
    echo "Failed to retrieve WG0 interface IP address."
    exit 1
  fi
  echo "WG0 interface IP address: $NGINX_LISTEN_IP"

  # Export NGINX_LISTEN_IP as an environment variable
  export NGINX_LISTEN_IP

  # Start docker compose with the NGINX_LISTEN_IP environment variable
  docker compose up -d || exit 1
  echo "Docker Compose started."
}

function logs() {
  docker compose logs "$@"
}

function stop() {
  docker compose down
  systemctl stop wg-quick@wg0
  echo "WireGuard interface wg0 is down."
}

function update() {
  # Resolve the absolute path to the project directory
  PROJECT_DIR=$(realpath "$(dirname "$0")")

  # Navigate to the project directory
  cd "$PROJECT_DIR" || exit 1

  # Fetch the latest changes from the remote repository
  echo "$(date): Fetching the latest changes from the prod branch..."
  if ! git fetch origin prod; then
    echo "$(date): Error: Failed to fetch from origin prod."
    exit 1
  fi

  # Compare the local prod branch with the remote prod branch
  LOCAL_HASH=$(git rev-parse prod)
  REMOTE_HASH=$(git rev-parse origin/prod)

  if [ "$LOCAL_HASH" = "$REMOTE_HASH" ]; then
    echo "$(date): No changes detected on the prod branch. Local repo is up to date."
  else
    echo "$(date): New commits detected. Updating to the latest version of the prod branch..."

    # Attempt to check out the prod branch
    if ! git checkout prod; then
      echo "$(date): Error: Failed to checkout prod branch."
      exit 1
    fi

    # Attempt to forcefully reset the local branch
    if ! git reset --hard origin/prod; then
      echo "$(date): Error: Failed to reset prod branch to origin/prod."
      exit 1
    fi

    echo "$(date): Repository successfully updated. Proceeding with Docker updates..."

    # Perform Docker updates
    docker compose down          # Stop and remove current containers
    docker compose build         # Build the updated containers
    docker compose up -d         # Start the new containers in detached mode

    echo "$(date): Update completed, production server is now running the latest prod branch."
  fi
}

case $COMMAND in
  check-dependencies)
    check_dependencies
    ;;
  fetch-config)
    fetch_config
    ;;
  build)
    build
    ;;
  check-wg)
    check_wg
    ;;
  start)
    start
    ;;
  logs)
    logs "$@"
    ;;
  stop)
    stop
    ;;
  update)
    update
    ;;
  *)
    echo "Usage: $0 {check-dependencies|fetch-config|build|check-wg|start|logs|stop|update}"
    exit 1
    ;;
esac