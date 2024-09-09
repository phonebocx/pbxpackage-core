#!/bin/bash
# vim: set ft=sh:

function find_partlabel_dev() {
  local lname=/dev/disk/by-partlabel/${1}
  [ -e "$lname" ] && readlink -f "$lname"
}

function is_cryptovol_open() {
  [ -e "$(get_cryptovol_dev $1)" ]
}

function get_cryptovol_dev() {
  echo /dev/disk/by-id/dm-name-${1}
}

function is_dev_encrypted() {
  local dev=$1
  if [ ! -b $dev ]; then
    echo "** Bug: $dev is not a device"
    exit 1
  fi
  CRYPTOINFO=$(cryptsetup luksDump $dev 2>/dev/null)
}

function mount_cryptovol() {
  local src=$1
  local keyfile=$2
  local srcdev=$(find_partlabel_dev $src)
  local mkfs=""
  local securevol="${src}.crypt"
  local mountpoint="/${src}"

  if grep -q " $mountpoint " /proc/mounts; then
    # It's already mounted
    return 0
  fi

  if [ ! "$srcdev" ]; then
    echo "** Bug: Tried to mount $src without a srcdev"
    exit 1
  fi
  if ! is_dev_encrypted $srcdev; then
    encrypt_dev $srcdev $keyfile
    if ! is_dev_encrypted $srcdev; then
      echo "** Bug: Tried to encrypt $srcdev, but it didn't stick"
      exit 1
    fi
    mkfs="true"
  fi
  if ! is_cryptovol_open $securevol; then
    open_encrypted_dev $srcdev $securevol $keyfile
  fi
  crypteddev=$(get_cryptovol_dev $securevol)
  # mkfs="true"
  if [ "$mkfs" ]; then
    mkfs.ext4 -F $crypteddev -L $securevol
  fi
  mkdir -p $mountpoint
  mount $crypteddev $mountpoint
  if [ "$mkfs" ]; then
    mkdir $mountpoint/data
    chmod 777 $mountpoint/data
  fi
}

function encrypt_dev() {
  local dev=$1
  local keyfile=$2
  if [ ! -b $dev ]; then
    echo "** Bug: $dev is not a device"
    exit 1
  fi
  if is_dev_encrypted $dev; then
    echo "** Bug: $dev is already encrypted"
    exit 1
  fi
  cryptsetup -q --hash sha512 --cipher aes-xts-plain64 --verbose luksFormat $dev $keyfile
}

function open_encrypted_dev() {
  local dev=$1
  local name=$2
  local keyfile=$3
  if [ ! -b $dev ]; then
    echo "** Bug: $dev is not a device"
    exit 1
  fi
  cryptsetup open $dev $name --key-file $keyfile
}

function close_encrypted_name() {
  local name=$1
  cryptsetup close $name
}

function unmount_cryptovol() {
  local src=$1
  local securevol="${src}.crypt"
  local mountpoint="/${src}"
  local attempts=10
  local crypteddev=$(get_cryptovol_dev $securevol)

  while [ "$attempts" -gt 0 ]; do
    if grep -q " $mountpoint " /proc/mounts; then
      # Unmount it
      umount $mountpoint
    fi
    if ! grep -q " $mountpoint " /proc/mounts; then
      break
    else
      sleep .5
    fi
    attempts=$(($attempts - 1))
  done
  if grep -q " $mountpoint " /proc/mounts; then
    echo "** Bug: Can't unmount $mountpoint, giving up"
    exit 1
  fi
  if is_cryptovol_open $securevol; then
    close_encrypted_name $securevol
  fi
}
