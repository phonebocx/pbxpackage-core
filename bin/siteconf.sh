#!/bin/bash

DIR="$(dirname "$(readlink -f "$0")")"
cd $DIR

. ./bootstrap.inc.sh

include_component install.inc

set -x

if does_siteconf_need_migration; then
    echo "I wanna migrate things"
    migrate_siteconf
fi
