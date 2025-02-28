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
            # Remove empty lines in the authkeys file
            grep -v '^$' "$keyfile" >>$authfile
            echo -e "\n# End of $keyfile\n" >>$authfile
        fi
    done

    # There's probably no way that this could ever NOT work, but, it's
    # better to be safe than try to figure out how to repair a million
    # devices that have glitched somehow

    # 1. Make sure authfile is not empty. If it is, don't do anything
    if [ ! -s $authfile ]; then
        echo "Somehow $authfile didn't exist, or is empty"
        return
    fi
    # 2. Double check it has at least one ssh key
    if ! grep -q '^ssh' $authfile; then
        echo "Could not find a ssh key in $authfile"
        return
    fi

    # We should be fine now.
    mkdir -p /root/.ssh
    rm -f /root/.ssh/authorized_keys.old
    # There always should be one, but maybe not?
    if [ ! -e /root/.ssh/authorized_keys ]; then
        touch /root/.ssh/authorized_keys
    fi
    mv /root/.ssh/authorized_keys /root/.ssh/authorized_keys.old
    cp $authfile /root/.ssh/authorized_keys
}
