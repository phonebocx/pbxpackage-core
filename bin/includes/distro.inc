#!/bin/bash
# vim: set ft=sh:

dfile=$BASEDIR/latest.dist

update_distfile() {
  local force=$1
  if [ ! "$distage" -o "$distage" == "0" ]; then
    if [ -e $dfile ]; then
      distage=$(($(date +%s) - $(stat --format=%Y $dfile)))
    else
      distage=0
    fi
  fi

  if [ "$distage" -gt 600 -o "$force" ]; then
    rm -f $dfile
    distage=0
    rfb=""
  fi

  # Lock so we don't clobber each other
  local distlock=/tmp/dist.lock
  if [ ! -e $distlock ]; then
    touch $distlock
    chmod 777 $distlock
  fi
  # If we can't lock after 10 seconds, exit subshell with code 56
  (
    flock -w 10 200 || exit 56
    if [ ! -e $dfile ]; then
      utilphp=$PHPBIN/util.php
      srcurl=$($utilphp --disturl 2>/dev/null)
      if [ ! "$srcurl" ]; then
        # Something broke the local PHP? we can't do anything
        exit
      fi
      rm -f $dfile.meta $dfile.sha256
      curl -Lsf $srcurl -o $dfile 2>/dev/null
      # If it exists and is not empty
      if [ -s "$dfile" ]; then
        curl -sf $(cat $dfile).meta -o $dfile.meta 2>/dev/null
        curl -sf $(cat $dfile).sha256 -o $dfile.sha256 2>/dev/null
      fi
      chmod 777 $dfile $dfile.meta $dfile.sha256 2>/dev/null
    fi
  ) 200>$distlock
  ERR=$?
  if [ "$ERR" == "56" ]; then
    echo "Unable to get lock when trying to update latest!" >/dev/stderr
    echo "Unable to get lock when trying to update latest!" >/dev/kmsg
    sleep 5
    return
  fi

  if [ ! "$rfb" ]; then
    if [ -s $dfile.meta ]; then
      rfb=$(grep 'fullbuild=' $dfile.meta | cut -d= -f2)
      rut=$(grep 'utime=' $dfile.meta | cut -d= -f2)
    else
      rfb="0"
      rut="0"
    fi
  fi
  if [ ! "$lfb" ]; then
    dvars=/distro/distrovars.json
    if [ ! -e $dvars ]; then
      lfb="2024.000-000"
      lut="0"
    else
      lfb=$(jq -r .buildver $dvars)
      lut=$(jq -r .buildutime $dvars)
    fi
  fi
}

get_latest_dist() {
  update_distfile
  echo ${rfb:-error}_${rut:-0}
}

get_current_dist() {
  update_distfile
  echo ${lfb:-error}_${lut:-0}
}

get_download_url() {
  update_distfile $1
  if [ -e "${dfile}" ]; then
    cat ${dfile}
  else
    echo "http://example.com/latest-redirect"
  fi
}

get_download_sha() {
  update_distfile
  if [ -e "${dfile}.sha256" ]; then
    cat ${dfile}.sha256
  else
    echo "unknown.sha.dfile"
  fi
}

is_upgrade_avail() {
  update_distfile
  [ "$lut" -lt "$rut" ]
}

function get_sysinfo_val() {
  local k=$(echo $1 | tr -cd '[:alnum:]._-')
  ../php/util.php --getsysinfo=$k
}
