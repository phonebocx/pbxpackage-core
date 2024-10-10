#!/bin/bash
# vim: set ft=sh:

function parseApiCmd() {
  local json=$1
  local section=$2

  local apicmd=""
  local apiurl=""
  local apifull=""
  local apiextras=""

  if [ ! -e "$json" ]; then
    echo "The json file '$json' does not exist."
    exit 1
  fi

  if [ -e $queuefile ]; then
    echo "Bug, api queue trying to run from apiqueue"
    exit 1
  fi

  for a in $(jq -r ".actions[$section].contents | keys[]" $json); do
    local v=$(jq -r ".actions[$section].contents.$a" $json)
    case ${a} in
      "url" ) apiurl=$v ;; 
      "request" ) apicmd=$v ;;
      "full" ) apifull=$v ;;
      "extras" ) apiextras=$v ;;
    esac
  done
  if [ ! "$apicmd" ]; then
    echo "Bug, no apicmd"
    exit 1
  fi

  # echo "Now: $apiurl, $apicmd, $apifull, $apiextras"

  if [ "$apiurl" -a "$apiurl" != "/" ]; then
    URLOVERRIDE=$apiurl
  fi

  echo "utime=$(date +%s)" > ${queuefile}.tmp
  [ "$apifull" == "true" ] && genApiData ${queuefile}.tmp
  genCurl $apicmd "$apiextras" ${queuefile}.tmp
  # echo "I am going to run '$CURL' into ${queuefile}"
  $CURL -o ${queuefile}
  rm -f ${queuefile}.tmp
}


