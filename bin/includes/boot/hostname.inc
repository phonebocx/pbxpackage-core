#!/bin/bash

set_system_hostname() {
    local hn=$1
    if [ ! "$hn" ]; then
        hn="unconf"
    fi
    hostname $hn

    # Update hosts
    cat > /etc/hosts <<EOF
# Autogen host files
127.0.0.1 localhost $hn
EOF
}