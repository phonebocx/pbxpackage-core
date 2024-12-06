#!/bin/bash
# vim: set ft=sh:

get_pkg_display() {
  echo "Currently installed packages:"
  $PHPBIN/util.php --pkgdisplay
  echo ""
}

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
  $PHPBIN/util.php --localpkginfo $pkg
}

get_remote_pkg_info() {
  local pkg=$1
  $PHPBIN/util.php --remotepkginfo $pkg
  # get_current_json | jq -j ".$p.commit,\"-\",.$p.utime,\"-\",.$p.modified"
}

# If this returns anything, the answer is yes.
pkg_needs_update() {
  local p=$1
  $PHPBIN/util.php --pkgneedsupdate $p
}

get_local_pkg_vers() {
  local p=$1
  $PHPBIN/util.php --pkgver $p
}
