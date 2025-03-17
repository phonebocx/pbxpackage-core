#!/bin/bash

export LC_ALL=C.UTF-8
export LANG=C.UTF-8

tput clear

# Before starting the main console, wait for the local system clock
# to be greater than the build timestamp
BUILDSTAMP=$(jq -r .buildutime /distro/distrovars.json 2>/dev/null)
# If that somehow failed, here's a random timestamp
[ ! "$BUILDSTAMP" ] && BUILDSTAMP=1740701672

while [ "$(date +%s)" -lt "$BUILDSTAMP" ]; do
    echo "System time incorrect!"
    echo "  Local system time should be at least $(date --date=@$BUILDSTAMP)"
    echo "  Current system time is $(date)"
    echo "  Please ensure this device has internet access."
    echo ""
    sleep 5
done

cd ./console
tmux -u new-session -s console "tmux source-file ./console.tmux"
echo "tty1 manager exited. This should will be restarted by getty@tty1"
sleep 1
