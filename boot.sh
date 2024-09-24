#!/bin/bash

# Change directory to wherever this file is located. That could
# be /pbxdev or /pbx
cd "$(dirname "$(readlink -f "$0")")"
if [ ! -e ./bin/real-boot.sh ]; then
    echo "I am in $(pwd) and I can not find ./bin/real-boot.sh"
    sleep 10
    exit 99
fi

# |& means 'redirect all file descriptors', not just stderr
./bin/real-boot.sh |& tee /var/log/pbxboot.log
