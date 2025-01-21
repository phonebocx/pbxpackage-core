#!/bin/bash

# This uses /usr/bin/php8.2 specifically to avoid killing
# any OTHER processes that are running as just 'php'. The
# version is not relevant, it's just that it not picked up
# by 'killall'.
PHP=/usr/bin/php8.2

PORT=4680
while :; do
    echo "Started root-only PHP server at $(date) listening on 127.0.0.1:$PORT"
    for p in factory pbxdev pbx; do
        l=/$p/core/php/loopback.php
        if [ -e "$l" ]; then
            if $PHP -l $l >/dev/null 2>&1; then
                echo "Launching $l"
                killall -q -9 -r php8.2
                $PHP -S 127.0.0.1:$PORT $l
                break
            else
                echo "Error checking failed on $l"
                php -l $l
            fi
        fi
    done
    echo "PHP server exited with $?, restarting"
    sleep 5
done
