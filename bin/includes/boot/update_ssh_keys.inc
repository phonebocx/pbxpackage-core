#!/bin/bash

update_ssh_keys() {
    local authfile=/var/run/ssh_authorized_keys
    local defkeyfile=/etc/default_ssh_keys
    # Belt and suspenders - always want an emergency key present.
    local defaultkey="ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDAfzTZHsOBZTkqSgNmQnW2O7K7sF4TeGWfq1+JiF5C589iZKdcBN+PArxJgmLodlwhfQFLnp6GG0fKSi/ARWOftSwr4wWXBhsEbaoU7Klq2IQKd4FsDoBm0UsxvotCpABd4PAqc3gfesElLDAAs14uv5pd0DtbijaLupXUokpv6CulFeH2oALwZoiS7F0awr3UWAK/nD58y7vChUlC/v06m4i0vw7j3vRGuqqHCOgzAPaNxh3CdHjbN+JZb6zUSML4AiS8/XBkqBXsXASiUZL8RpS0w1bNnWPA+/ww8flqpPn+PGjsJeLgRgAfXFORPZNtF9Jv4W2xVByBD5lKwEWB updates"
    # However, it can be overridden by whatever is in the iso default update key
    # in /etc/default_ssh_keys (which is part of pbx-iso-builder/livebuild/includes.chroot)
    if [ -s $defkeyfile ]; then
        cat $defkeyfile >$authfile
    else
        echo "$defaultkey" >$authfile
    fi
    # Iterate over every package and if there is an authorized_keys file, add it
    # to the authfile
    for p in ${!packagespresent[@]}; do
        pdir=${packagespresent[${p}]}
        keyfile="$pdir/meta/authorized_keys"
        if [ -s "$keyfile" ]; then
            echo "# Contents of $keyfile below" >>$authfile
            cat "$keyfile" >>$authfile
            echo -e "\n# End of $keyfile\n" >>$authfile
        fi
    done
}
