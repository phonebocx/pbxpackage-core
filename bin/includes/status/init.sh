#!/bin/bash

load_all_status() {
  for x in $CDIR/status/*; do
    f=$(basename $x)
    if [ "$f" != "init.sh" -a -x $x ]; then
      . $x
    fi
  done
}

load_all_status


