#!/bin/bash

source $CDIR/functions/packages.inc

get_pkg_display() {
  echo "Currently installed packages:"
  $CDIR/../php/util.php --pkgdisplay
  echo ""
}

