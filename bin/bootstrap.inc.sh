#!/bin/bash
# vim: set ft=sh:

COREDIR="$(dirname "$(readlink -f "$0")")"
INCDIR="$COREDIR/includes"

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

export BASEDIR=/var/run/distro
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

export packagespresent

# CDIR is the 'best' coredir, not neccesarily this one.
CDIR=${packagespresent["core"]}
PHPBIN="$CDIR/php"
UTILPHP="$PHPBIN/util.php"

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

# Slightly complicated code here.
#  Usage 1:
#    trigger_hooks hookname "packages to exclude"
#    In quotes, with spaces seperating them
#  Usage 2:
#    trigger_hooks hookname onlypackagename
#    Eg, 'trigger_hooks install onlycore'
#
# BONUS: You might need to hand some params to the hook.
# If so, set the "HOOKPARAMS" variable before calling this.
#
# IMPORTANT: As I don't trust myself, it is unset before returning.
#
trigger_hooks() {
  hookname=$1
  pkgfilter=$2
  declare -A skippkgs
  if [ "${pkgfilter:0:4}" == "only" ]; then
    onlypackage=${pkgfilter:4}
    if [ "$onlypackage" == "core" ]; then
      onlypackage="realcore"
    fi
  else
    onlypackage=""
    for x in $pkgfilter; do
      skippkgs["${x}"]=true
    done
    # Never run any hook from origcore
    skippkgs["origcore"]=always
    # If we were told to skip core, ALSO add realcore
    if [ "${skippkgs["core"]}" ]; then
      skippkgs["realcore"]=core
    fi
  fi

  # Always run core hooks before any others, so cheat by calling it "realcore"
  for p in realcore ${!packagespresent[@]}; do
    if [[ "$onlypackage" && "$onlypackage" != "$p" ]]; then
      continue
    fi
    if [ "${skippkgs["$p"]}" ]; then
      # Package is excluded
      continue
    fi
    if [ "$p" == "core" ]; then
      # We would have already run 'realcore' below if we were meant to
      continue
    fi
    if [ "$p" == "realcore" ]; then
      # It's core, not realcore
      p="core"
    fi
    PACKAGEDIR=${packagespresent[${p}]}
    PACKAGENAME=$p
    HOOKFILE="$PACKAGEDIR/meta/hooks/$hookname"
    if [ -x "$HOOKFILE" ]; then
      . $HOOKFILE $HOOKPARAMS
    fi
  done
  # You'll need to reset this if you're trying call trigger_hooks
  # in some smart way.
  unset HOOKPARAMS
}
