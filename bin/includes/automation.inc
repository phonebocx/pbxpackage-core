#!/bin/bash

function update_os() {
    include_component sysupdate.sh
    if [ "$(get_running_version)" == "live" ]; then
        echo "Updates not available when running live"
        return
    fi
    # forcefully update the distfile
    update_distfile force
    # Now this will be accurate
    if ! is_upgrade_avail; then
        echo "No OS Update required"
        return
    fi
    # SKIPHASH=true
    OVERWRITE=true
    # auto_remote_update is a cut down version of remote_update from dialog/update.sh
    auto_remote_update
    grep -q " $SRC_ROOT " /proc/mounts && umount $SRC_ROOT
    if [ "$INSTALLED" ]; then
        echo "No reboot!"
        #reboot
    fi
}

function update_packages() {
    if [ ! -e /usr/local/bin/pkgupdate ]; then
        echo "Can't check for package updates, as /usr/local/bin/pkgupdate is missing."
        sleep 5
        return
    fi

    check=$(/usr/local/bin/pkgupdate --check)
    STATUS=$?
    if [ "$STATUS" -ne 0 ]; then
        echo "Critical error ($STATUS) when running /usr/local/bin/pkgupdate, can not continue"
        echo "More information: $check"
        sleep 10
        return
    fi

    if [ ! "$check" ]; then
        echo "No new or updated packages available"
        return
    fi

    echo "There are new or updated packages available, starting update service"
    systemctl start pkgupdate.service
    # This should be killed, but exit anyway
    #exit 55
}
