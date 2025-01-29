#!/bin/bash

while :; do
  echo "$(date): Starting update check"
  for x in pbxdev pbx factory; do
    f="/$x/core/bin/updates.sh"
    if [ ! -e "$f" ]; then
      # echo "$f does not exist, skipping"
      continue
    else
      echo "Found $f"
      $f
      break
    fi
  done
  echo "Sleepy"
  sleep 5
done
