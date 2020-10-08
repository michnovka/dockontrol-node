# DOCKontrol node

This is code for peripheral Raspberry Pi with relay that works together with https://github.com/michnovka/dockontrol

## Requirements

PHP 7.3+, PHP-CURL extension

## Hardware

I use Raspberry Pi4 together with https://www.waveshare.com/wiki/RPi_Relay_Board_(B) relay board. Commands are sent using Relay.sh script (must be added to sudoers file since it requires root privileges)

###Notes

On 3-relay board the mapping for Relay.sh is CH8 ->1, CH6 -> 2, CH7 -> 3