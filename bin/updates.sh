#!/bin/bash

DIR="$(dirname "$(readlink -f "$0")")"
. $DIR/bootstrap.inc.sh

include_component install/common-functions
include_component install/iso-functions
include_component automation.inc
include_component distro.inc

cd /

# This is different to /tmp/poll.lock - That one stops manual
# updates happening at the same time, this one stops AUTOMATED
# updates happening at the same time.
# update_us and update_packages lock /tmp/poll.lock so if we
# lock it, nothing can run!
LOCKFILE=/tmp/updates.lock

if [ ! -e $LOCKFILE ]; then
  touch $LOCKFILE
  chmod 777 $LOCKFILE
fi

echo "$(date): Started package and OS update check"
# This only proceeds if we can get an exclusive lock on $LOCKFILE
(
  flock -n -e 200 || exit 56

  # This checks to see if there's an OS update. If there is, it installs
  # it and reboots the machine.
  update_os

  # If there aren't any OS updates, what about packages? This uses the
  # /usr/local/bin/pkgupdate tool to check and install packages. This
  # function is defined in automation.inc. If there ARE packages that
  # need to be updated, everything is shut down and restarted by the
  # systemd pkgupdate service. Basically, if we get a result, there
  # are no packages to update
  update_packages
) 200>$LOCKFILE

ERR=$?
if [ "$ERR" == "56" ]; then
  echo "Unable to get lock. Sleeping for 10 seconds"
  sleep 10
else
  # Sleep for 5 or so hours before checking again
  OFFSET=$((1 + $RANDOM % 7200))
  PERIOD=$((18000 + $OFFSET))
  echo "Package and OS update check completed successfully."
  echo "$(date): Sleeping for $PERIOD seconds before checking again"
  sleep $PERIOD
fi
