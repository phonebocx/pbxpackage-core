#!/bin/bash

mount -o remount,rw /run/live/medium
touch /run/live/medium/redo-siteconf
sync
/sbin/reboot -f
