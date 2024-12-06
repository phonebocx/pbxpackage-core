#!/bin/bash
# vim: set ft=sh:

include_component distro.inc
include_component dialog/askinstall.sh
include_component dialog/persistence.sh
include_component dialog/update.sh
include_component automation.inc
# This is for Clearly IP hardware, with a dedicated Run-mode LED
include_component i2c.inc

DIALOG_OK=0
DIALOG_CANCEL=1
DIALOG_HELP=2
DIALOG_EXTRA=3
DIALOG_ITEM_HELP=4
DIALOG_ESC=255

width=40
height=25

dialog_outfile=$(mktemp /tmp/dialog.XXXXXX)
trap "rm -f /tmp/dialog.*" 0 1 2 5 15

declare -A assocoptions
declare -a menuoptions

get_dialog_outfile() {
  if [ ! "$dialog_outfile" ]; then
    dialog_outfile=$(mktemp /tmp/dialog.XXXXXX)
  fi
  # If the variable has been set, but the file doesn't exist, recreate it
  if [ ! -e "$dialog_outfile" ]; then
    dialog_outfile=$(mktemp /tmp/dialog.XXXXXX)
  fi
}

get_string() {
  local t=${1:-"No Title"}
  local b=${2:-"No body"}
  local init=$3
  get_dialog_outfile
  dialog --clear --backtitle "$buildvers" --title "$t" $DSHADOW \
    --inputbox "$b" $height $width "$init" 2>$dialog_outfile
}

get_assoc_menu() {
  local t=${1:-"No Title"}
  local m=${2:-"No Menu title"}
  local lookupmap
  get_dialog_outfile
  menuoptions=()
  ptr=1
  for key in $(echo ${!assocoptions[@]} | tr ' ' '\n' | sort); do
    val=${assocoptions[${key}]}
    menuoptions+=($ptr "$val")
    lookupmap[$ptr]=$key
    ptr=$(($ptr + 1))
  done

  get_menu "$t" "$m"
  tmpchoice=$(<$dialog_outfile)
  choice=""
  if [ ! "$tmpchoice" ]; then
    return
  fi
  choice=${lookupmap[${tmpchoice}]}
}

get_menu() {
  local t=${1:-"No Title"}
  local m=${2:-"No Menu title"}
  get_dialog_outfile
  dialog --clear --backtitle "$buildvers" --title "$t" $DSHADOW \
    --menu "$m" $height $width 15 "${menuoptions[@]}" 2>$dialog_outfile
}

ask_yesno() {
  local t=${1:-"No Title"}
  local b=${2:-"No body"}
  local defaultno=""
  local timeout=""
  if [ "$3" ]; then
    defaultno="--defaultno"
  fi
  if [ "$4" ]; then
    timeout="--timeout $4"
  fi

  dialog --clear --backtitle "$buildvers" --title "$t" $DSHADOW \
    $defaultno $timeout --yesno "$b" $height $width
}

infobox() {
  local t=${1:-"No Title"}
  local b=${2:-"No body"}
  dialog --backtitle "$buildvers" --title "$t" $DSHADOW \
    --infobox "$b" $height $width
}

msgbox() {
  local t=${1:-"No Title"}
  local b=${2:-"No body"}
  dialog --backtitle "$buildvers" --title "$t" $DSHADOW \
    --msgbox "$b" $height $width
}
