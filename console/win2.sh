#!/bin/bash

DIR="$(dirname "$(readlink -f "$0")")"
. $DIR/functions/common.inc
. $DIR/status/init.sh

#sstatus=$(get_sysinfo_val servicestatus)
#[ ! "$sstatus" ] && sstatus="Unknown System Status"
#
#echo "$sstatus"

for meta in $DIR/../../*/meta; do
  [ ! -d $meta ] && continue;
  [ ! -e $meta/hooks/info ] && continue;
  OUT="$($meta/hooks/info)"
  if [ "$OUT" ]; then
    [ -e $meta/packagename ] && cat $meta/packagename
    echo "$OUT"
  fi
done


