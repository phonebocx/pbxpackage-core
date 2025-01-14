#!/bin/bash

PORT=4680
while :; do
    echo "Started root-only PHP server at $(date) listening on 127.0.0.1:$PORT"
    for p in factory pbxdev pbx; do
        l=/$p/core/php/loopback.php
        if [ -e "$l" ]; then
            if php -l $l >/dev/null 2>&1; then
                echo "Launching $l"
                killall -q -9 /usr/bin/php
                /usr/bin/php -S 127.0.0.1:$PORT $l
            else
                echo "Error checking failed on $l"
                php -l $l
            fi
        fi
    done
    echo "PHP server exited with $?, restarting"
    sleep 5
done
