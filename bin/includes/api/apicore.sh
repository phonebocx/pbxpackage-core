#!/bin/bash
# vim: set ft=sh:

include_component api/parsedownload.sh
include_component api/parsecommand.sh
include_component api/parselog.sh
include_component api/parseapicmd.sh

BASEAPI=https://api.sendfax.to
APIURL=${BASEAPI}/device/v1

# This is duplicated in functions/install/common-functions, but
# this is used by gl-boot only
get_running_version() {
  local img=$(awk '{ print $1 }' /proc/cmdline | cut -d= -f2)
  if [ "${img:0:5}" == "/boot" ]; then
    echo $img | cut -d/ -f3
  else
    echo live
  fi
}

function genCurl() {
  CURL="curl -s "
  local cmd=${1:-poll}
  local params=${2}
  local datafile=$3
  local settings=${4:-svceid,eid,tokuuid}
  local tmpfile=$(mktemp)
  local url=${APIURL}/${cmd}
  if [ "$URLOVERRIDE" ]; then
    # Note: Bashism
    if [[ "$URLOVERRIDE" == http* ]]; then
      url=${URLOVERRIDE}
    elif [[ "$URLOVERRIDE" == /* ]]; then
      url=${BASEAPI}${URLOVERRIDE}
    else
      url=${APIURL}/${URLOVERRIDE}
    fi
    URLOVERRIDE=""
  fi

  running=$(get_running_version)
  sysid=$(get_sysinfo_val systemid)
  prod_uuid=$(cat /sys/class/dmi/id/product_uuid 2>/dev/null)

  if [ "$params" ]; then
    url="$url?${params}&booted=$running&sysid=$sysid&product_uuid=$prod_uuid&fax=v2"
  else
    url="$url?booted=$running&sysid=$sysid&product_uuid=$prod_uuid&fax=v2"
  fi

  for s in $(echo $settings | tr ',' '\n'); do
    [ "$s" != "null" ] && echo "$s=$(get_sysinfo_val $s)" >>$tmpfile
  done

  if [ "$datafile" ]; then
    if [ "$datafile" == "apidata" ]; then
      genApiData $tmpfile
    elif [ "$datafile" == "fullapidata" ]; then
      genApiData $tmpfile true
    elif [ -e "$datafile" ]; then
      cat $datafile >>$tmpfile
      echo >>$tmpfile
    else
      echo "Error: Was asked to write from $datafile but it does not exist."
      exit 1
    fi
  fi

  while read -r v; do
    [ "$v" ] && CURL="${CURL}--data $(echo $v | tr ' ' '_') "
  done <$tmpfile

  if [ -e /etc/wireguard/public ]; then
    CURL="${CURL} --data-urlencode wgpublic@/etc/wireguard/public "
  fi

  CURL="${CURL}$url"
  rm -f $tmpfile
}

function genApiData() {
  local outfile=$1
  local withdahdi=$2
  local tmpfile=$(mktemp)
  ip -o link | sed -r '/eth?/ s/.+ (eth.).+ether ([0-9a-f:]+) .+/\1=\2/' | grep ^eth >$tmpfile

  for x in /sys/class/dmi/id/*; do
    if [ ! -e $x -o -d $x ]; then
      continue
    fi
    b=$(basename $x)
    if [ "$b" == "uevent" -o "$b" == "modalias" ]; then
      continue
    fi
    echo $b=$(cat $x) >>$tmpfile
  done

  if [ "$withdahdi" ]; then
    echo "dahdiscan=$(get_dahdi_scan | base64 -w0)" >>$tmpfile
  fi

  if [ "$outfile" ]; then
    if [ ! -e "$outfile" ]; then
      echo "Error: Was asked to write to $outfile but it does not exist."
      rm -f $tmpfile
      exit 1
    fi
    cat $tmpfile >>$outfile
  else
    cat $tmpfile
  fi
  rm -f $tmpfile
}

function parseResponse() {
  local file=$1
  local recursion=$2
  local l
  local tmpfile
  local hook
  if [ ! -e "$file" ]; then
    echo "The json file '$file' does not exist."
    exit 1
  fi
  for l in "$(grep '^[{\[]' $file)"; do
    tmpfile=$(mktemp)
    echo "$l" >$tmpfile
    parseResponseLine $tmpfile
    HOOKPARAMS="$tmpfile"
    trigger_hooks parseresp
    rm -f $tmpfile
  done

  # Generate sysinfo.ini because it's useful. Outputs
  # an ini format to /var/run/distro/sysinfo.ini
  [ -e /usr/local/bin/util ] && /usr/local/bin/util --allsysinfo=ini

  # Now, if there was an api callback, do that.
  #  -- queuefile is set in api.inc
  if [ ! "$recursion" -a -e "$queuefile" ]; then
    parseResponse $queuefile true
    rm -f $queuefile
  fi
}

function parseResponseLine() {
  local json=$1
  if [ ! -e "$json" ]; then
    echo "The json line file '$json' does not exist."
    exit 1
  fi

  for id in $(jq -r '.actions | keys[]' $json); do
    local type=$(jq -r ".actions[$id].type" $json)
    # meta/hooks/parseresp handles info lines
    [ "$type" == "files" ] && parseDownload $1 $id
    [ "$type" == "command" ] && parseCommand $1 $id
    [ "$type" == "b64command" ] && parseB64Command $1 $id
    [ "$type" == "log" ] && parseLog $1 $id
    [ "$type" == "api" ] && parseApiCmd $1 $id
  done
}
