#!/bin/bash

DIR="$(dirname "$(readlink -f "$0")")"
cd $DIR

. ./bootstrap.inc.sh

include_component install.inc

mount_siteconf

