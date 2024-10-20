#!/bin/bash
# vim: set ft=sh:

DIR="$(dirname "$(readlink -f "$0")")"
. $DIR/bootstrap.inc.sh
set -x
trigger_hooks install
echo "I am in $(pwd) now"
