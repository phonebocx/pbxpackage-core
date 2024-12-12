#!/bin/bash

auto_remote_update() {
  include_component fattr.inc
  include_component spinner.inc
  include_component install/grub-functions
  include_component install/install-functions
  include_component install/partition-tools
  include_component install/siteconf-functions

  if ! grep -q " /spool " /proc/mounts; then
    echo -e "There is no local spool available.\n\nA spool directory is required to download the update ISO.\n\nPlease install $brandname, which will create the local storage, and then upgrade."
    return 1
  fi
  if grep -q " $ISO_MOUNT " /proc/mounts; then
    echo -e "The mount point $ISO_MOUNT is already in use by something.\n\nPlease unmount whatever is there, and retry.\n"
    return 1
  fi

  url=$(get_download_url force)
  isofile=$(basename $url | tr -c -d '[:graph:]' | tr -d ';&*')
  SRC_ROOT="/spool/$isofile"
  sha=$(get_download_sha)

  # This is used to skip over some checks in the standard tools
  AUTOUPDATE=true

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
      echo -e "Downloading $isofile\n\n"
      curl --progress-bar -L "$url" -o $SRC_ROOT 2>&1
    fi
    if [ ! -s $SRC_ROOT ]; then
      echo -e "\nThe file $SRC_ROOT was corrupt and has been deleted.\n\nPlease try again."
      rm -f $SRC_ROOT
      return 1
    fi
    echo -e "\n$isofile has been downloaded.\n\nThe size of the downloaded file is $(stat -c %s $SRC_ROOT | numfmt --to=iec).\n\nChecking sha256, please wait."
    currentsha=$(get_attribute $SRC_ROOT sha256)
    # Update it if it's missing
    if [ ! "$currentsha" ]; then
      currentsha=$(sha256sum $SRC_ROOT | cut -d\  -f1)
      set_attribute $SRC_ROOT sha256 "$currentsha"
    fi
    if [ "$currentsha" != "$sha" ]; then
      rm -f $SRC_ROOT
      echo -e "\nThe file $isofile had the checksum $currentsha, it should have been $sha.\n\nThe file has been deleted."
      sleep 3
      return 1
    fi
    mount_iso
    auto_update_from_mnt
  ) 200>$LOCKFILE

  exit 9
  ERR=$?
  if [ "$ERR" == "56" ]; then
    infobox "Unable to Lock" "\nAn update is currently being performed.\n\nPlease try again later."
    sleep 3
    return
  fi
}

auto_update_from_mnt() {
  newver=$(get_version $SRC_ROOT)
  if [ ! "$newver" -o "$newver" == "UNKNOWN" ]; then
    umount $SRC_ROOT
    echo -e "Version '$newver' is not a valid $brandname image.\n\nCan not continue."
    sleep 5
    return
  fi
  auto_update_from_folder $SRC_ROOT
}

auto_update_from_folder() {
  SRC_ROOT=$1
  newver=$(get_version $SRC_ROOT)
  if [ ! "$newver" -o "$newver" == "UNKNOWN" ]; then
    echo -e "\nVersion '$newver' is not a valid $brandname image in $SRC_ROOT.\n\nCan not continue."
    sleep 1
    grep -q " $SRC_ROOT " /proc/mounts && umount $SRC_ROOT
    return
  fi

  if [ ! "$EFI_PARTITION" ]; then
    EFI_PARTITION=$(find_efi_partition)
  fi

  DRIVE=$(echo $EFI_PARTITION | sed -r 's@/dev/([sv]d.|mmcblk.)(p?[[:digit:]])$@\1@')
  if [ ! -b "/dev/$DRIVE" ]; then
    echo "**** BUG: Found existing EFI partition $EFI_PARTITION but /dev/$DRIVE is not a block device"
    sleep 10
    exit 1
  fi

  running=$(get_running_version)
  if [ "$running" == "$newver" ]; then
    FORCEDNAME=$newver"a"
  fi

  # This is in includes/install/install-functions
  do_install

  # Remove old images befre regenerating grub
  cleanup_old_installs

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
    echo 'I would reboot'
  fi
}
