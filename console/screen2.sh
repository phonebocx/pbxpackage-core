#!/bin/bash
#
while :; do
    echo "$(date): Asterisk console started"
    P=$(pidof asterisk)
    if [ ! "$P" ]; then
        echo "$(date): Asterisk not running. Trying again in 30 seconds"
        sleep 30
        continue
    fi
    asterisk -rvvvvvv
    sleep 1
done
