# Dockontrol Node

This is code for peripheral Raspberry Pi with relay that works together with https://github.com/michnovka/dockontrol

## Requirements

Docker (tested on v27)

## Hardware

I use Raspberry Pi4 together with https://www.waveshare.com/wiki/RPi_Relay_Board_(B) relay board. Commands are sent using Relay.sh script (must be added to sudoers file since it requires root privileges)

## Installation

First make sure that you generated a node in DOCKontrol CP and save its API keys

```
git clone
cd dockontrol-node

# Edit the .env file
cp .env.example .env
nano .env

# Build and run docker
docker compose build
docker compose up -d
```

If configured properly, the docker will set up wg0 tunnel to the main dockontrol server.

### Auto-update

You can set-up auto-update (enable `AUTO_UPDATE=1` in `.env`) by configuring a cron that runs as root

```crontab
0 * * * *  /bin/bash path/to/project/scripts/auto_update.sh
```

## Notes

On 3-relay board the mapping for Relay.sh is CH8 ->1, CH6 -> 2, CH7 -> 3