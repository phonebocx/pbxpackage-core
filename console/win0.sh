#!/bin/bash

while :; do
    ./console_launch.sh win0
    status=$?
    if [ "$status" == "5" ]; then
        tput clear
        echo "Updating."
        sleep 5
        exit
    fi
    echo "Restarting main menu, status was $status"
    sleep 1
done
