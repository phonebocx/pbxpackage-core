#!/bin/bash

export LC_ALL=C.UTF-8
export LANG=C.UTF-8

cd ./console
tmux -u new-session -s console "tmux source-file ./console.tmux"
echo "Restarting..."
sleep 1
