#!/bin/bash
# vim: set ft=sh:

get_current_json() {
  $PHPBIN/util.php --pkgjson
}

get_local_packages() {
  $PHPBIN/util.php --localpkgs
}

get_remote_packages() {
  $PHPBIN/util.php --remotepkgs
}

get_local_pkg_info() {
  local pkg=$1
  $PHPBIN/util.php --localpkginfo $p
}

get_remote_pkg_info() {
  local p=$1
  $PHPBIN/util.php --remotepkginfo $p
  # get_current_json | jq -j ".$p.commit,\"-\",.$p.utime,\"-\",.$p.modified"
}

# Returning 1 is false, 0 is true
pkg_needs_update() {
  local p=$1
  # Exit value is returned
  $PHPBIN/util.php --updateavail $p
}

get_local_pkg_vers() {
  local p=$1
  $PHPBIN/util.php --pkgver $p
}

