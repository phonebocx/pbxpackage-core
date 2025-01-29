#!/bin/bash

# This runs the automation.sh script continuously.
# That script should run, and then sleep for a period of time,
# don't exit immediately.

A=/usr/local/bin/automation.sh
while :; do
    if [ ! -e "$A" ]; then
        echo "Can't find Automation script $A - sleeping for 30 seconds and trying again"
        sleep 30
        continue
    fi
    echo "$(date): Starting $A"
    $A
done
