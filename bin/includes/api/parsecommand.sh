#!/bin/bash
# vim: set ft=sh:

function parseCommand() {
  local json=$1
  local section=$2

  if [ ! -e "$json" ]; then
    echo "The json file '$json' does not exist."
    exit 1
  fi

  for id in $(jq -r ".actions[$section].contents | keys[]" $json); do
    cmd=$(jq -r ".actions[$section].contents[$id].cmd" $json)
    echo "In $id I would run $cmd"
    $cmd
  done
}

function parseB64Command() {
  local json=$1
  local section=$2

  if [ ! -e "$json" ]; then
    echo "The json file '$json' does not exist."
    exit 1
  fi

  for id in $(jq -r ".actions[$section].contents | keys[]" $json); do
    rm -f /tmp/b64cmd
    jq -r ".actions[$section].contents[$id].encodedcmd" $json | base64 -d > /tmp/b64cmd
    chmod 755 /tmp/b64cmd
    echo "In $id I found a base64 encoded command"
    cat /tmp/b64cmd
    /tmp/b64cmd
  done
}


