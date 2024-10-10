#!/bin/bash
# vim: set ft=sh:

function parseLog() {
  local json=$1
  local section=$2
  local logentry
  local loglevel

  if [ ! -e "$json" ]; then
    echo "The json file '$json' does not exist."
    exit 1
  fi

  for id in $(jq -r ".actions[$section].contents | keys[]" $json); do
    logentry=$(jq -r ".actions[$section].contents[$id].logentry" $json)
    loglevel=$(jq -r ".actions[$section].contents[$id].loglevel" $json)
    echo "In $id I would log '$logentry' at '$loglevel'"
  done
}


