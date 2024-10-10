#!/bin/bash
# vim: set ft=sh:
queuefile=$BASEDIR/apiqueue

include_component api/apicore.sh
include_component status/dahdi.sh
include_component distro.inc
