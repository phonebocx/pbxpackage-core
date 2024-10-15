#!/bin/bash


DIR="$(dirname "$(readlink -f "$0")")"
cd $DIR
. ./bootstrap.inc.sh

include_component install.inc
include_component distro.inc

set -x
is_upgrade_avail
