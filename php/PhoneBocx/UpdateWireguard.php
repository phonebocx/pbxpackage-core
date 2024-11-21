<?php

namespace PhoneBocx;

class UpdateWireguard
{

    private $src = "/var/run/wireguard/wg0.conf";
    private $dest = "/etc/wireguard/wg0.conf";
    private $privkey = "/etc/wireguard/priv.key";
    private $pubkey = "/etc/wireguard/public";

    public function go()
    {
        if (!file_exists($this->privkey) || !file_exists($this->pubkey)) {
            @unlink($this->dest);
            $this->stopWireguard();
            return;
        }
        $privkey = trim(file_get_contents($this->privkey));
        $pubkey = trim(file_get_contents($this->pubkey));
        if (!file_exists($this->src)) {
            return;
        }
        $new = str_replace("__PRIVATEKEY__", $privkey, file_get_contents($this->src));
        // Make sure our pubkeys match
        if (!preg_match('/PUBKEY=(.+)$/m', $new, $out)) {
            // There wasn't a pubkey in the returned info?? This service is disabled.
            $this->stopWireguard();
            return;
        }

        if ($out['1'] != $pubkey) {
            // This pubkey does not match our private key. This is REALLY REALLY wrong.
            @unlink($this->dest);
            $this->stopWireguard();
            return;
        }

        // OK, the pubkey matches our private key. We can see if anything's changed now.
        if (file_exists($this->dest)) {
            $current = file_get_contents($this->dest);
        } else {
            $current = "";
        }
        if ($current != $new) {
            // Something changed. We want to update
            file_put_contents($this->dest, $new);
            $changed = true;
        } else {
            $changed = false;
        }
        if (strpos($new, 'Address') === false) {
            $this->stopWireguard();
        } else {
            $this->startWireguard($changed);
        }
    }

    public function stopWireguard()
    {
        if (file_exists("/sys/class/net/wg0")) {
            print "Stop the wireguard!\n";
            exec("/usr/bin/wg-quick down wg0", $output, $ret);
        } else {
            print "It already is stopped\n";
        }
    }

    public function startWireguard($changed)
    {
        if ($changed) {
            // If it's changed, and it's already running, shut it down
            if (file_exists("/sys/class/net/wg0")) {
                exec("/usr/bin/wg-quick down wg0", $output, $ret);
            }
        }
        if (!file_exists("/sys/class/net/wg0")) {
            exec("/usr/bin/wg-quick up wg0", $output, $ret);
        }
    }
}
