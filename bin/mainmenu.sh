#!/bin/bash

DIR="$(dirname "$(readlink -f "$0")")"
. $DIR/bootstrap.inc.sh

include_component dialog.inc
include_component install.inc

# Check screen resolution
if [ -e /sys/class/graphics/fb0/virtual_size ]; then
  fbsize=$(cat /sys/class/graphics/fb0/virtual_size | tr ',' 'x')
  if [ "$fbsize" != "1024x768" ]; then
    ##msgbox "Display Problem" "\nThe current screen resolution of this device is '$fbsize', which is not recommended.\n\nIf you are running this inside VMware, find the option 'svga.guestBackedPrimaryAware' in the .vmx file and set it to FALSE.\n\nThis screen will probably look strange until this is fixed.\n"
    msgbox "Display Problem" "\nThe current screen resolution of this device is '$fbsize', which is not recommended.\n\nYou will need to reboot this machine with a compatible screen connected to resolve this.\n\nThis screen may look strange until this is fixed.\n"
  fi
fi

declare -A assocoptions
declare -A assocfunctions

while :; do
  if ! is_phonebocx_installed; then
    ask_to_install
  fi
  assocoptions=(
    [01check]="Check for Package Updates"
    [10update]="Check for OS Updates"
    [11devel]="Enable Debug mode"
    [98restart]="Restart Services"
    [99reboot]="Reboot Device"
  )

  assocfunctions=(
    [01check]="package_updates"
    [10update]="remote_update"
    [12grub]="force_reinstall_grub"
    [15persist]="update_persistence"
    [19zap]="force_zap"
    [20reinstall]="ask_to_install"
    [40delimg]="del_grub_entry"
  )

  if [ -e /tmp/devel ]; then
    assocoptions[11devel]="Disable Debug mode"
    assocoptions[12grub]="Reinstall bootloader"
    assocoptions[15persist]="Persistence"
    assocoptions[16destruct]="Enable Destructive"
    assocoptions[20reinstall]="Ask to install again"
    assocoptions[40delimg]="Prune OS Images"
    if [ -e /tmp/destruct ]; then
      assocoptions[16destruct]="Disable Destructive"
      assocoptions[19zap]="Wipe device totally"
    fi
  fi

  # If any packages have a menuhook, run that
  for p in ${!packagespresent[@]}; do
    PACKAGEDIR=${packagespresent[${p}]}
    menuhook=$PACKAGEDIR/menu/mainmenuhook.sh
    if [ -e $menuhook ]; then
      . $menuhook
    fi
  done

  # Make sure it's empty from last time
  unset choice
  get_assoc_menu "$brandname" "\nPlease select an option from below\n"

  if [ ! "$choice" ]; then
    exit
  fi

  # Do we have a programmed function?
  if [ "${assocfunctions[${choice}]}" ]; then
    callfunc=${assocfunctions[${choice}]}
    $callfunc
    sleep 5
  else
    case ${choice} in
    "11devel")
      [ -e /tmp/devel ] && rm -f /tmp/devel || touch /tmp/devel
      ;;
    "16destruct")
      [ -e /tmp/destruct ] && rm -f /tmp/destruct || touch /tmp/destruct
      ;;
    "98restart")
      systemctl restart getty@tty1
      sleep 1
      exit
      ;;
    "99reboot")
      reboot
      ;;
    *)
      echo "BUGBUGBUG!"
      echo "Nothing available to handle '$choice' - did something break?"
      echo "Restarting in 5 secs..."
      sleep 5
      exit
      ;;
    esac
  fi

  status=$?
  if [ "$status" == "5" ]; then
    # We are updating. Don't restart
    exit 5
  fi
done
