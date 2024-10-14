#!/bin/bash

# As this should be run in the initrd, if there is a /root/usr/bin/bash
# we want to add /root/usr/bin and /root/usr/sbin to the path, too
if [ -x /root/usr/bin/bash ]; then
    export PATH=/root/usr/bin:/root/usr/sbin:$PATH
    export LD_LIBRARY_PATH=/root/usr/lib/x86_64-linux-gnu:$LD_LIBRARY_PATH
fi

DIR="$(dirname "$(readlink -f "$0")")"
cd $DIR
. ./bootstrap.inc.sh

include_component install.inc

# Before we do the migration, are we rolling back?
check_redo_siteconf_migration

if does_siteconf_need_migration; then
    migrate_siteconf
fi

# To make sure we can roll back, old sshd doesn't understand the 'Include'
# keyword. Which means ssh breaks if you try to roll back. This is not
# a good thing when we want to fix things!
for s in /root/etc/ssh/sshd_config /etc/ssh/sshd_config; do
    [ -e $s ] && sed -i 's/^Include/# Include/' $s
done
