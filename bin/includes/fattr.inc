#!/bin/bash
# vim: set ft=sh:

function set_attribute() {
    [ ! -e /usr/bin/setfattr ] && return
    f=$1
    [ ! -e "$f" ] && return
    k=user.$2
    v=$3
    if [ "$v" == "__delete__" ]; then
        /usr/bin/setfattr -x $k $f
    else
        /usr/bin/setfattr -n $k -v "$v" $f
    fi
}

function get_attribute() {
    [ ! -e /usr/bin/getfattr ] && return
    f=$1
    [ ! -e "$f" ] && return
    k=user.$2
    /usr/bin/getfattr --only-values --absolute-names -n $k $f 2>/dev/null
}
