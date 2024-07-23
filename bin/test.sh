#!/bin/bash

DIR="$(dirname "$(readlink -f "$0")")"
. $DIR/bootstrap.inc.sh

include_component install.inc

install_os
exit

mount_siteconf
mount_siteconf
mount_siteconf
mount_siteconf

