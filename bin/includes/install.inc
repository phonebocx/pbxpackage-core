#!/bin/bash
# vim: set ft=sh:

# Default grub config is in grub-functions

include_component spinner.inc
include_component install/common-functions
include_component install/core-functions
include_component install/install-functions
include_component install/partition-functions
include_component install/partition-tools
include_component install/iso-functions
include_component install/grub-functions
include_component install/grub-tools
include_component install/siteconf-functions

install_os() {
    DRIVE=sda
    SRC_ROOT=/dev/sr0
    set -x
    do_install
    install_grub
    unmount_efi_partition
    unmount_rw_conf_partition
}
