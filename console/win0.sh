#!/bin/bash

. ./console_wrapper_inc.sh

while :; do
    launch_console win0
    echo Exited with $?
    sleep 5
done
