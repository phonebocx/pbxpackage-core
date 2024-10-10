#!/bin/bash

get_dahdi_scan() {
  if [ -e /dev/dahdi/ctl ]; then
    if [ ! -e $BASEDIR/dahdi_scan ]; then
      /usr/sbin/dahdi_scan > $BASEDIR/dahdi_scan
    fi
    cat $BASEDIR/dahdi_scan
  fi
}

get_port_count() {
  get_dahdi_scan | grep -c ^port=
}

get_port_type() {
  portnum=$1
  o=$(get_dahdi_scan | grep "^port=${portnum}," | cut -d, -f2)
  if [ "$o" ]; then
    echo $o
  else
    echo "UNKNOWN"
  fi
}

get_port_status() {
  local portnum=$1
  local l=$(awk "/ $portnum /" /proc/dahdi/1 | sed -E 's/\(EC.+$//g')
  if [ ! "$l" ]; then
    echo -n " -- Unknown Error"
    return
  fi
  if echo $l | grep -q RED; then
    echo -n "(Fault)"
    return
  fi

  if echo $l | grep -q '(In use)'; then
    return
  fi
  echo -n "(Unconfigured)"
}


