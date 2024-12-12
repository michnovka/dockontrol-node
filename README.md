# Dockontrol Node

This is code for peripheral Raspberry Pi with relay that works together with https://github.com/michnovka/dockontrol

## Requirements

Docker (tested on v27), wg-quick, wireguard kernel module loaded

## Hardware

I use Raspberry Pi4 together with https://www.waveshare.com/wiki/RPi_Relay_Board_(B) relay board. Commands are sent using Relay.sh script (must be added to sudoers file since it requires root privileges)

## Installation

First make sure that you generated a node in DOCKontrol CP and save its API keys

```
git clone https://github.com/michnovka/dockontrol-node
cd dockontrol-node

git checkout prod

# Edit the .env file
cp .env.example .env
nano .env

# Check dependencies
./dockontrol-node.sh check-dependencies

# Install, set up startup and build containers
./dockontrol-node.sh install

# Start
./dockontrol-node.sh start
```

### Auto-update

You can set up auto-update by configuring a cron that runs as root.
The server will then check every so often if there are new commits in the `prod` branch and if yes
then take pull and rebuild and restart containers

```crontab
0 * * * *  /bin/bash path/to/project/dockontrol-node.sh update
```

## Relay boards

We support 2 types of relay boards from WaveShare:
[3-relays](https://www.waveshare.com/wiki/RPi_Relay_Board(B)) and [8-relays](https://www.waveshare.com/wiki/RPi_Relay_Board_(B))

They differ in GPIO pin mapping to actuators, so in order to add support, configure in `.env`
```dotenv
RELAY_BOARD_TYPE=WAVESHARE_3_PIN|WAVESHARE_8_PIN
```

## TODO

setup cron script in dockontrol-node.sh