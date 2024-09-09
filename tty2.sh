#!/bin/bash

export LC_ALL=C.UTF-8
export LANG=C.UTF-8

echo "This is tty2"
cd ./console
sleep 1
tmux -u new-session -s console "tmux source-file ./console.tmux"
sleep 1
