<?php

namespace PhoneBocx;

use PhoneBocx\Models\FastAGIAbstract;

class FastAGI extends FastAGIAbstract
{
    public static string $iam = 'core';

    public function agi_runled()
    {
        $l = new LedController();

        $params = $this->m->fastagi->getAllParams();
        if (empty($params[1])) {
            $t = $l->toggleRunLed();
            $this->m->fastagi->verbose("Toggled Run LED - Result $t");
        } else {
            $cmd = $params[1];
            $t = $l->setRunLed($cmd);
            $this->m->fastagi->verbose("Set run led to $cmd, Result $t");
        }
    }

    public function agi_portled()
    {
        $l = new LedController();
        $params = $this->m->fastagi->getAllParams();
        $srcportnum = (int) $params[1];
        $mode = (string) $params[2];
        $t = $l->setPortLed($srcportnum, $mode);
        $this->m->fastagi->verbose("Set port led $srcportnum led to $mode, Result $t");
    }

    public function agi_reboot()
    {
        // Something bad has happened. Just reboot. This can be called
        // by Fax when all the 2's starts to happen.
        $this->m->fastagi->verbose("Sending S to sysreq-trigger, sleeping for 1 sec.");
        file_put_contents("/proc/sysrq-trigger", "s");
        sleep(1);
        $this->m->fastagi->verbose("Rebooting.");
        exec('/sbin/reboot -f');
        $this->m->fastagi->verbose("I have finished rebooting. I am now ded.");
    }
}
