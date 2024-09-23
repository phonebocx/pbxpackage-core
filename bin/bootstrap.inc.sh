#!/bin/bash
# vim: set ft=sh:

COREDIR="$(dirname "$(readlink -f "$0")")"
INCDIR="$COREDIR/includes"
PHPBIN="$COREDIR/php"

export DIALOGRC=$COREDIR/dialogrc

CONFLABEL=7680d283ce83
SPOOLLABEL=spool
RECOVERYLABEL=recovery

shortname=DistroName
brandmame=Unbranded
buildver="v0 Unreleased"
SRC_ROOT=/run/live/medium
# This should have a rw mount of sda4
WR_CONF=/mnt/rwconf
# Where the efi part is mounted
EFI_CONF=/mnt/rwefi
ISO_MOUNT=/mnt/iso_mount

BASEURL=https://repo.phonebo.cx
API=https://api.phonebo.cx/api
LATESTISO=$BASEURL/latest.iso

BASEDIR=/var/run/distro
LOGDIR=$BASEDIR/distrolog

OVERWRITE=yeah

if [ -d /spool/data ]; then
  CORESQLITE=/spool/data/core.sq3
else
  CORESQLITE=$BASEDIR/core.sq3
fi

DEBUG=true

# Find all packages that are here. Prefer /pbxdev, so look
# there first before /pbx
declare -A packagespresent
for d in /pbxdev/* /pbx/*; do
  [ ! -d $d ] && continue
  [ ! -e $d/meta/packagename ] && continue
  package=$(basename $d)
  [ ! "${packagespresent[$package]}" ] && packagespresent[$package]=$d
done

# There should be /distro/distrovars.sh which has all the variables in it
# for branding and stuff
[ -f /distro/distrovars.sh ] && . /distro/distrovars.sh

[ ! "$buildvers" ] && buildvers="$shortname $buildver"

if [ ! -d $LOGDIR ]; then
  mkdir -p $LOGDIR
  chmod 777 $LOGDIR
fi

debug_msg() {
  [ "$DEBUG" ] && error_msg "$*"
}

error_msg() {
  echo "$*" >/dev/stderr
  echo "$*" >/dev/kmsg
}

declare -A already_included
include_component() {
  incfile=$1
  reinclude=$2
  fullpath="$INCDIR/$incfile"
  if [ ! -e "$fullpath" ]; then
    error_msg " ** ERR: Tried to include_once $fullpath but it does not exist"
    exit 1
  fi
  [ "$reinclude" ] && already_included["${fullpath}"]=""
  if [ "${already_included["${fullpath}"]}" ]; then
    debug_msg "Skipping $fullpath, already included"
  else
    already_included["${fullpath}"]=true
    . ${fullpath}
  fi
}
