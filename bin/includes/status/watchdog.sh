#!/bin/bash

has_watchdog() {
  [ -e /sys/devices/virtual/watchdog/watchdog0/identity ]
}

get_watchdog_status() {
  if ! has_watchdog; then
    echo No hardware watchdog detected
    return
  fi
  echo "System watchdog ('$(cat /sys/devices/virtual/watchdog/watchdog0/identity)') is currently $(cat /sys/devices/virtual/watchdog/watchdog0/state) with a $(cat /sys/devices/virtual/watchdog/watchdog0/timeout) second timeout."
}



