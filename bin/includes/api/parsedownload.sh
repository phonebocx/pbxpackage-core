#!/bin/bash
# vim: set ft=sh:

function parseDownload() {
  local json=$1
  local section=$2
  local unmountefi=""
  local efidisk=$([ -e /dev/disk/by-label/EFI ] && readlink -f /dev/disk/by-label/EFI)
  local efimounted=$([ "$efidisk" ] && grep "$efidisk /efi " /proc/mounts | cut -d\  -f2)
  local destfile

  if [ ! -e "$json" ]; then
    echo "The json file '$json' does not exist."
    exit 1
  fi

  for id in $(jq -r ".actions[$section].contents | keys[]" $json); do
    fn=$(jq -r ".actions[$section].contents[$id].filename" $json)
    loc=$(jq -r ".actions[$section].contents[$id].loc" $json)
    destfile=""
    if [ "$loc" == "boot" ]; then
      destfile=$boot/$fn
    elif [ "$loc" == "root" ]; then
      destfile=$fn
    elif [ "$loc" == "efi" ]; then
      if [ "$efidisk" ]; then
        if [ ! "$efimounted" ]; then
          mkdir -p /efi
          mount $efidisk /efi
          efimounted="/efi"
          unmountefi="true"
        fi
      fi
      if [ "$efimounted" ]; then
        destfile=$efimounted/$fn
      else
        echo Skipping $fn as it is meant to go to EFI and I have no idea where that is
        continue
      fi
    fi
    if [ ! "$destfile" ]; then
      echo "Don't know where to put $fn with locl $loc"
      sleep 5
      continue
    fi
    mkdir -p $(dirname $destfile)
    rm -f $destfile
    url=$(jq -r ".actions[$section].contents[$id].url" $json)
    if [ "$url" != "null" ]; then
      curl -s -o $destfile $url
    else
      jq -r ".actions[$section].contents[$id].contents" $json > $destfile
    fi
  done
  if [ "$unmountefi" ]; then
    umount $efimounted
  fi
}


