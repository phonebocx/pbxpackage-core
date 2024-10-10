#!/bin/bash

get_link_stats() {
  for l in $(ip -o link | awk '/eth.:/ { print $2,$3 }' | sed -e 's/[<>]//g' -e 's/: /=/'); do
    n=${l%%=*}
    if [[ "$l" == *NO-CARRIER* ]]; then
      echo -n "$n: Down "
    else
      echo -n "$n: Up   "
    fi
  done
  echo ""
}

