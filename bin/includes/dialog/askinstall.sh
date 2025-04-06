#!/bin/bash

ask_to_install() {
  run_led med
  # If we have 'wipeall' on /proc/cmdline, then assume YES, but only for internal mmc storage
  if grep -q 'wipeall' /proc/cmdline; then
    if [ -e /dev/mmcblk1 ]; then
      run_led slow
      sync
      sgdisk --zap-all /dev/mmcblk1 >/dev/null 2>&1
      partprobe
      blkdiscard -f /dev/mmcblk1 >/dev/null 2>&1
      echo 3 >/proc/sys/vm/drop_caches
      partprobe
      dialog_install
      return
    fi
  fi

  local timeout=10
  if [ "$1" ]; then
    timeout=""
  fi

  if ask_yesno "Install Required" "\nThis machine does not have a valid installation\n\nWould you like to install $brandname now?\n" "" $timeout; then
    dialog_install
  else
    echo "Not installing"
    sleep 5
  fi
}

dialog_install() {
  destdevice=""
  if grep -q 'wipeall' /proc/cmdline; then
    if [ -e /dev/mmcblk1 ]; then
      destdevice=mmcblk1
    fi
  fi
  if [ ! "$destdevice" ]; then
    # This is in install/common-functions
    ask_about_storage
  fi
  if [ ! "$destdevice" ]; then
    return
  fi
  if does_partition_exist $destdevice 1; then
    ask_to_nuke
  fi
  # Does it STILL exist? They chose no.
  if does_partition_exist $destdevice 1; then
    return
  fi

  # Now we can just do our install
  tput clear
  DRIVE=$destdevice
  do_install
  # destdevice is used with non-uefi devices
  install_grub $destdevice
  siteconf_checks
  unmount_rw_conf_partition
  if grep -q 'wipeall' /proc/cmdline; then
    reboot
  fi
  if ask_yesno "Reboot now?" "\nYou should now reboot into $brandname.\n\nWould you like to reboot now?"; then
    reboot
  fi
}

force_zap() {
  destdevice=""
  grep -q $WR_CONF /proc/mounts && umount $WR_CONF
  ask_about_storage "Select which drive to nuke"
  if [ ! "$destdevice" ]; then
    return
  fi
  infobox "Please wait.." "\nPlease wait. The device /dev/$destdevice is being erased"
  grep -q $WR_CONF /proc/mounts && umount $WR_CONF
  grep -q " /spool " /proc/mounts && umount /spool
  sgdisk --zap-all /dev/$destdevice >/dev/null 2>&1
  blkdiscard /dev/$destdevice >/dev/null 2>&1
  sync
  /sbin/reboot -f
}

ask_to_nuke() {
  if ask_yesno "Erase Device" "\nThe device /dev/$destdevice already has partitions on it.\n\nDo you want to delete these partitions?\n\nIf you choose no, you will not be able to install $brandname this device."; then
    infobox "Please wait.." "\nPlease wait. The device /dev/$destdevice is being erased"
    sgdisk --zap-all /dev/$destdevice >/dev/null
    blkdiscard /dev/$destdevice
    sync
    echo 3 >/proc/sys/vm/drop_caches
    partprobe
  else
    infobox "Not Erasing" "\nNot erasing all data on /dev/$destdevice \n"
    sleep 5
  fi
}

ask_about_storage() {
  local lookupmap
  local msg="Please select which drive to install to from the list below"
  if [ "$2" ]; then
    msg="$2"
  fi
  destdevice=""
  find_usable_storage
  if [ ${#DEVICES[@]} -eq 0 ]; then
    echo "Crash and burn. No devices to install to"
    exit
  fi
  assocoptions=()
  for dev in ${DEVICES[@]}; do
    assocoptions[$dev]="/dev/$dev"
  done

  get_assoc_menu "Select Drive" "$msg"
  destdevice=$choice
}
