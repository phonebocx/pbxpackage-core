#!/bin/bash

function get_script_loc() {
    local pkgname=$1
    local scriptname=$2
    local dirs="/factory/$1 /pbxdev/$1 /pbx/$1"
    for d in $dirs; do
        sfile=$d/$scriptname.sh
        if [ -x $sfile ]; then
            echo $sfile
            return
        fi
    done
}

function launch_console() {
    APPNAME=$1
    echo "Launching $APPNAME"
    sleep 1
    B=$(get_script_loc core bin/bootstrap.inc)
    if [ ! -x "$B" ]; then
        echo "Can't execute $B, or can't find bootstrap.inc, can not continue"
        sleep 5
        continue
    fi
    cd $(dirname $B)
    . $B
    W=$(get_script_loc core bin/console/$APPNAME)
    if [ ! -x "$W" ]; then
        echo "Can't execute $W from $APPNAME, or can not find it."
        sleep 5
        continue
    fi
    . $W
    echo "Exit with $?"
    sleep 1
}
