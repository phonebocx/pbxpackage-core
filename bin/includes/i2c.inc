#!/bin/bash
# vim: set ft=sh:

get_i2c_bus() {
  local f
  local x
  local bus

  if [ "$1" ]; then
    rm -f $BASEDIR/i2cbus
  fi

  if [ ! -e $BASEDIR/i2cbus ]; then
    f=$(i2cdetect -l | awk '/Synopsys/ { print $1 }' | cut -d- -f2)
    for b in $f; do
      x=$(i2cget -y $b 0x55 0x0 2>/dev/null)
      if [ $? -eq 0 ]; then
        bus=$b
        break
      fi
    done

    if [ ! "$bus" ]; then
      echo "Unable to find STM! Not running on Clearly IP hardware?" >/dev/stderr
      echo -n >$BASEDIR/i2cbus
    fi
    echo -n "$bus" >$BASEDIR/i2cbus
  fi

  cat $BASEDIR/i2cbus
}

function get_range_as_hex() {
  local bus=$(get_i2c_bus)
  if [ ! "$bus" ]; then
    return
  fi
  local start=$1
  local end=$2
  local ret=""
  for x in $(seq $start $end); do
    ret="$ret"$(i2cget -y $bus 0x55 $x | sed 's/^0/\\/')
  done
  echo $ret
}

function get_all_i2c_as_hex() {
  get_range_as_hex 0 255
}

function get_run_led_value() {
  local bus=$(get_i2c_bus)
  if [ ! "$bus" ]; then
    return
  fi
  i2cget -y $bus 0x55 41
}

function start_i2c_watchdog() {
  local bus=$(get_i2c_bus)
  if [ "$bus" ]; then
    i2cset -y $bus 0x55 21 1
    i2cset -y $bus 0x55 22 0
    i2cset -y $bus 0x55 23 2
    touch $BASEDIR/watchdog_active
  fi
}

function tickle_i2c_watchdog() {
  if [ ! -e $BASEDIR/watchdog_active ]; then
    return
  fi

  local bus=$(get_i2c_bus)

  i2cset -y $bus 0x55 24 1
  echo $(date +%s)":  Poked the watchdog"
  printf "$(get_range_as_hex 0 31)" | xxd
}

function run_led() {
  local mode=$1
  local val=0
  case $mode in
  "off") val=0 ;;
  "flash") val=1 ;;
  "slow") val=1 ;;
  "on") val=2 ;;
  "longon") val=2 ;;
  "fast") val=3 ;;
  "med") val=4 ;;
  esac
  set_run_led $val
}

function set_run_led() {
  local val=$1
  local bus=$(get_i2c_bus)
  if [ "$bus" ]; then
    i2cset -y $bus 0x55 41 $val
  fi
}

function toggle_run_led() {
  local bus=$(get_i2c_bus)
  if [ ! "$bus" ]; then
    return
  fi

  # If it's on, turn it off. If it's off, turn it on.
  local n=$(i2cget -y $bus 0x55 41)
  if [ "$n" == "0x00" ]; then
    run_led on
  else
    run_led off
  fi
}
