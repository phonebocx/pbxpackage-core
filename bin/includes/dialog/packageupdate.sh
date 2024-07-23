#!/bin/bash

include_component packages.inc

package_updates() {
  current=$(get_running_version)
  if [ "$current" == "live" ]; then
    msgbox "Can not update" "\nCan not update packages when running from live."
    return
  fi

  tput clear
  if [ ! -e /usr/bin/pkgupdate ]; then
    msgbox "Updater Missing" "\nThe $brandname package updater is missing.\n\nI don't know what to do from here."
    return
  fi

  check=$(/usr/bin/pkgupdate --check)

  if [ ! "$check" ]; then
    infobox "Nothing to update" "\nNo packages available to update"
    sleep 3
    return
  fi

  echo "Starting the pkgupdate service now"
  systemctl start pkgupdate.service
  exit 5
}
