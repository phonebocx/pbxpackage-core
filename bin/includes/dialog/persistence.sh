#!/bin/bash

update_persistence() {
  current=$(get_running_version)
  if [ "$current" == "live" ]; then
    echo "Can not set up persistence when running from live."
    sleep 5
    return
  fi
  cmount=$(grep $CONF_PARTITION /proc/mounts | head -1 | cut -d\  -f2)
  if [ ! "$cmount" ]; then
    echo Bug - Can not find cmount from $CONF_PARTITION
    sleep 5
    exit 1
  fi

  WR_CONF=$cmount
  proot=""
  mount_siteconf
  root_is_persistent && proot="true"
  unmount_siteconf

  if [ "$proot" ]; then
    ask_remove_proot
  else
    ask_add_proot
  fi
}


ask_add_proot() {
  if ask_yesno "Persist /" "\nWould you like to enable persistent storage?\n\nThis is normally only used in debugging.\nIf you select yes, the machine will immediately reboot.\n"; then
    set_persistent_root
    sync
    reboot
  fi
}

ask_remove_proot() {
  if ask_yesno "Persist /" "\nWould you like to remove persistent storage?\n\nThis will return the machine to its standard operating mode.\n\nData you have stored will still be available under /run/live/persistence/loop1/rw if you need to access it.\n\nIf you select yes, the machine will immediately reboot.\n"; then
    del_persistent_root
    sync
    reboot
  fi
}

