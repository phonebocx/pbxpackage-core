#!/bin/bash
# vim: set ft=sh:

# This is launched by:
#  1. systemd phonebocx-boot.service which executes
#  2. /usr/local/bin/phonebocx-boot.sh which finds the first 'boot.sh' in /factory, /pbxdev or /pbx
#  3. /pbx/core/boot.sh Which chdirs to /pbx/core/bin and then runs this.

# The output of this is saved to /var/log/pbxboot.log

DIR="$(dirname "$(readlink -f "$0")")"
. $DIR/bootstrap.inc.sh

# Make sure our rundir is correct
mkdir -p /var/run/phonebocx
chmod 0777 /var/run/phonebocx

include_component crypto.inc

[ ! "$UUID" ] && UUID=/sys/class/dmi/id/product_uuid
rm -f /var/run/crypto.key
[ -e $UUID ] && cat $UUID >/var/run/crypto.key

# If this device doesn't have a crypto key, use a generic one.
if [ ! -s /var/run/crypto.key ]; then
  echo 36e26dd9-91a0-4547-b197-bf28ce57cfe9 >/var/run/crypto.key
fi
# 12345678-1234-5678-901b-cddeefaabbcc is a known bad UUID. Don't
# try to use it.
if grep -q '^12345678' /var/run/crypto.key; then
  echo "Invalid system crypto key, can not create/mount spool"
else
  sdev=$(find_partlabel_dev spool)
  if [ "$sdev" ]; then
    mount_cryptovol spool /var/run/crypto.key
    rm -f /var/run/crypto.key
  fi
fi

# If /spool is mounted, make sure /spool/data exists
if grep -q ' /spool ' /proc/mounts; then
  mkdir -p /spool/data
  chmod 777 /spool/data
fi

# If any packages have an install hook, run it
#   (This function is in bootstrap.inc.sh)
trigger_hooks install

include_component boot/hostname.inc
include_component boot/webresources.inc

link_webres

# Now /usr/local/bin/util should exist and be correct
if [ ! -e /usr/local/bin/util ]; then
  HOSTNAME=noutil
else
  HOSTNAME=$(/usr/local/bin/util --getsysinfo=systemid 2>/dev/null)
fi
if [ "$HOSTNAME" ]; then
  set_system_hostname $HOSTNAME
else
  set_system_hostname unconf
fi

# Update /root/.ssh/authorized_keys from ISO defaults
# and any packages added.
include_component boot/update_ssh_keys.inc

update_ssh_keys
