#!/bin/bash

remote_update() {
  if ! grep -q " /spool " /proc/mounts; then
    msgbox "No spool!" "\nThere is no local spool available.\n\nA spool directory is required to download the update ISO.\n\nPlease install $brandname, which will create the local storage, and then upgrade."
    return
  fi
  if grep -q " $ISO_MOUNT " /proc/mounts; then
    msgbox "Mount Error" "\nThe mount point $ISO_MOUNT is already in use by something.\n\nPlease unmount whatever is there, and retry.\n"
    return
  fi

  iso=$(get_download_url force)

  if [ -e /tmp/devel ]; then
    get_string "Update Source" "\nPlease enter the URL to pull an update from\n\nThis probably will not be configurable.\n\n" "$iso"
    url=$(<$dialog_outfile)
    if [ ! "$url" ]; then
      return
    fi
  else
    url=$iso
  fi
  isofile=$(basename $url | tr -c -d '[:graph:]' | tr -d ';&*')
  SRC_ROOT="/spool/$isofile"
  sha=$(get_download_sha)

  # This lock is shared by everything that does updates.
  LOCKFILE=/tmp/poll.lock
  if [ ! -e $LOCKFILE ]; then
    touch $LOCKFILE
    chmod 777 $LOCKFILE
  fi

  # We only continue if nothing ELSE is doing updates.
  (
    flock -n -e 200 || exit 56

    if [ ! -e $SRC_ROOT ]; then
      tput clear
      echo -e "Downloading $isofile\n\n"
      curl --progress-bar -L "$url" -o $SRC_ROOT 2>&1
    fi
    if [ ! -s $SRC_ROOT ]; then
      msgbox "ISO Error!" "\nThe file $SRC_ROOT was corrupt and has been deleted.\n\nPlease try again."
      rm -f $SRC_ROOT
      return
    fi
    infobox "Validating ISO" "\n$isofile has been downloaded.\n\nThe size of the downloaded file is $(stat -c %s $SRC_ROOT | numfmt --to=iec).\n\nChecking sha256, please wait."
    currentsha=$(sha256sum $SRC_ROOT | cut -d\  -f1)
    if [ "$currentsha" != "$sha" ]; then
      rm -f $SRC_ROOT
      infobox "Checksum Error!" "\nThe file $isofile had the checksum $currentsha, it should have been $sha.\n\nThe file has been deleted."
      sleep 3
      return
    fi
    mount_iso
    update_from_mnt
  ) 200>$LOCKFILE

  ERR=$?
  if [ "$ERR" == "56" ]; then
    infobox "Unable to Lock" "\nAn update is currently being performed.\n\nPlease try again later."
    sleep 3
    return
  fi
}

update_from_mnt() {
  newver=$(get_version $SRC_ROOT)
  if [ ! "$newver" -o "$newver" == "UNKNOWN" ]; then
    umount $SRC_ROOT
    msgbox "Invalid Version" "\nVersion '$newver' is not a valid $brandname image.\n\nCan not continue."
    sleep 5
    return
  fi
  update_from_folder $SRC_ROOT
}

update_from_folder() {
  SRC_ROOT=$1
  newver=$(get_version $SRC_ROOT)
  if [ ! "$newver" -o "$newver" == "UNKNOWN" ]; then
    msgbox "Invalid Version" "\nVersion '$newver' is not a valid $brandname image in $SRC_ROOT.\n\nCan not continue."
    sleep 1
    grep -q " $SRC_ROOT " /proc/mounts && umount $SRC_ROOT
    return
  fi

  if [ ! "$EFI_PARTITION" ]; then
    EFI_PARTITION=$(find_efi_partition)
  fi

  DRIVE=$(echo $EFI_PARTITION | sed -r 's@/dev/(sd.|mmcblk.)(p?[[:digit:]])$@\1@')
  if [ ! -b "/dev/$DRIVE" ]; then
    echo "**** BUG: Found existing EFI partition $EFI_PARTITION but /dev/$DRIVE is not a block device"
    sleep 10
    exit 1
  fi

  if [ -e /tmp/devel ]; then
    get_string "Updating $DRIVE" "\nPlease enter the name for this image.\n\nYou can not overwrite the current running image, so you may need to chose a new name.\n" "$newver"
    name=$(cat $dialog_outfile | tr -c -d '[:graph:]' | tr -d ';&*')
    if [ ! "$name" ]; then
      grep -q " $SRC_ROOT " /proc/mounts && umount $SRC_ROOT
      return
    fi
    if [ "$name" != "$newver" ]; then
      FORCEDNAME=$name
    fi
  fi
  tput clear
  do_install
  # This should always exist, but maybe it was deleted or something?
  if [ -e "$WR_CONF/boot/grub/grub.cfg" ]; then
    generate_grub_cfg "$WR_CONF/boot/grub"
  else
    install_grub
  fi
  siteconf_checks
  if [ "$dismount" != "no" ]; then
    umount $WR_CONF
  fi

  grep -q " $SRC_ROOT " /proc/mounts && umount $SRC_ROOT
  if [ ! "$FATAL" -a ! -e /tmp/devel ]; then
    reboot
  fi
}
