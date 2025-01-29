#!/bin/bash

if [ -e /pbxdev/core/bin/bootstrap.inc.sh ]; then
  ROOT=/pbxdev/core/bin
elif [ -e /pbx/core/bin/bootstrap.inc.sh ]; then
  ROOT=/pbx/core/bin
elif [ -e /factory/core/bin/bootstrap.inc.sh ]; then
  ROOT=/factory/core/bin
else
  echo "Can not find bootstrap.inc anywhere! Major system error."
  echo "Sleeping for 30 seconds, and hopefully it's fixed by then"
  sleep 30
  exit 1
fi

cd $ROOT
. ./bootstrap.inc.sh
# Overwrite the include and coredir so we don't hold anything open
COREDIR=$ROOT
INCDIR=$ROOT/includes

include_component install/common-functions
include_component distro.inc
include_component automation.inc

cd /var/run/distro

LOCKFILE=/tmp/poll.lock
if [ ! -e $LOCKFILE ]; then
  touch $LOCKFILE
  chmod 777 $LOCKFILE
fi

echo "automation.sh started"
date

# This only proceeds if we can get an exclusive lock on $LOCKFILE
(
  flock -n -e 200 || exit 56
  echo "$(date): Lock granted"

  # This checks to see if there's an OS update. If there is, it installs
  # it and reboots the machine.
  update_os

  # If there aren't any OS updates, what about packages?
  update_packages
  # If this exits 55, it means there are packages that need to be updated,
  # so we need to exit too.
  PKGUPDATE=$?
  [ "$PKGUPDATE" == 55 ] && exit 55

) 200>$LOCKFILE

ERR=$?
if [ "$ERR" == "56" ]; then
  echo "Unable to get lock. Sleeping for 10 seconds."
  sleep 10
else
  # Sleep for 5 hours minimum, plus up to 2 hours more
  OFFSET=$((1 + $RANDOM % 7200))
  PERIOD=$((18000 + $OFFSET))
  echo "automation.sh sleeping for $PERIOD seconds"
  date
  sleep $PERIOD
fi
