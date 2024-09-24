#!/bin/bash
# vim: set ft=sh:

DIR="$(dirname "$(readlink -f "$0")")"
. $DIR/bootstrap.inc.sh

set -x

# Make sure our rundir is correct
mkdir -p /var/run/phonebocx
chmod 0777 /var/run/phonebocx

include_component crypto.inc

[ ! "$UUID" ] && UUID=/sys/class/dmi/id/product_uuid
rm -f /var/run/crypto.key
[ -e $UUID ] && grep -v "^12345678-" $UUID >/var/run/crypto.key
if [ ! -s /var/run/crypto.key ]; then
  echo 36e26dd9-91a0-4547-b197-bf28ce57cfe9 >/var/run/crypto.key
fi
sdev=$(find_partlabel_dev spool)
if [ "$sdev" ]; then
  mount_cryptovol spool /var/run/crypto.key
  rm -f /var/run/crypto.key
fi

# If /spool is mounted, make sure /spool/data exists
if grep -q ' /spool ' /proc/mounts; then
  mkdir -p /spool/data
  chmod 777 /spool/data
fi

# If any packages have an install hook, run it
for p in ${!packagespresent[@]}; do
  [ "$p" == "origcore" ] && continue
  PACKAGEDIR=${packagespresent[${p}]}
  PACKAGENAME=$p
  boothook=$PACKAGEDIR/meta/hooks/install
  if [ -e $boothook ]; then
    . $boothook
  fi
done

HOSTNAME=phonebocx

include_component boot/hostname.inc
include_component boot/webresources.inc

set_system_hostname $HOSTNAME
link_webres
