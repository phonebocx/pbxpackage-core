#!/bin/bash

get_link_stats() {
  for nic in $(ls /sys/class/net); do
    if [[ "$nic" == eth* ]]; then
      padded=$(printf "%-5s" $(cat /sys/class/net/$nic/operstate))
      echo -n "$nic: ${padded^} "
    fi
  done
  echo ""
}

