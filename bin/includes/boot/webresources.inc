#!/bin/bash

link_webres() {
    for p in ${!packagespresent[@]}; do
        PACKAGEDIR=${packagespresent[${p}]}
        if [ -d $PACKAGEDIR/html ]; then
            rm -rf /var/www/html/$p
            ln -s $PACKAGEDIR/html /var/www/html/$p
        fi
    done
    rm -f /var/www/html/index*
    COREINDEX=${packagespresent["core"]}/index.php
    ln -s $COREINDEX /var/www/html/index.php
}
