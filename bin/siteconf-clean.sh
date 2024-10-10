#!/bin/bash

umount /oldpersist
umount /persist
umount /recovery
wipefs --all --force /dev/sda3
