#!/bin/bash

# This is launched by core/console/win1 which has already included bootstrap
include_component dahdi.inc
include_component packages.inc
include_component distro.inc
include_component nics.inc

# Refresh screen on console in case kernel junk ended up there
tmux refresh-client -t /dev/tty1 2>/dev/null

sysid=$(get_sysinfo_val systemid)
if [ ! "$sysid" ]; then
  echo -e "ClearlyIP Fax Device - NO SYSTEM ID (Serial No $(cat /sys/class/dmi/id/product_serial))"
else
  echo -e "ClearlyIP Fax Device - System ID $sysid (Serial No $(cat /sys/class/dmi/id/product_serial))"
fi

# get_watchdog_status
cat /etc/motd
echo ""

if is_upgrade_avail; then
  echo "*****  Online Upgrade Available -- $(get_latest_dist) *****"
else
  echo ""
fi

ipaddr=$(ip -o addr | grep inet\  | egrep -v '\ (lo|wg)' | awk '{ print $4 }' | cut -d/ -f1 | tr '\n' ' ')
echo -ne "System $(uptime -p). IP \033[1m${ipaddr}\033[0m-- "
get_link_stats
echo ""

pcount=$(get_port_count)
if [ "$pcount" -eq 0 ]; then
  echo "    No DAHDI Hardware detected"
else
  get_dahdi_scan | $CDIR/php/util.php --parsedahdiscan
fi
echo ""
get_pkg_display

#JOBS=$($DIR/../php/queue.php --count 2>/dev/null)
#[ "$JOBS" ] && echo "$JOBS local job(s) awaiting processing"
