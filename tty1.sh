#!/bin/bash

COREDIR="$(dirname "$(readlink -f "$0")")"
$COREDIR/bin/mainmenu.sh

echo "Sleeping 1 sec before restart in $COREDIR"
sleep 1
