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

    // Something bad has happened. Just reboot. This can be called
    // by Fax when all the 2's starts to happen.
    public function agi_reboot()
    {
        // Sync filesystem
        $this->m->fastagi->verbose("Sending S to sysreq-trigger, playing a 2 second long file.");
        file_put_contents("/proc/sysrq-trigger", "s");
        $this->m->fastagi->exec('Playback', 'an-error-has-occurred');
        // remount everything it can ro. No idea how long this will take, and if the
        // mmc itself is derped, it'll never complete.
        $this->m->fastagi->verbose("Sending U to sysreq-trigger playing a 2 second long file.");
        $this->m->fastagi->exec('Playback', 'something-terribly-wrong');
        file_put_contents("/proc/sysrq-trigger", "u");
        $this->m->fastagi->verbose("Rebooting.");
        // Kaboom.
        file_put_contents("/proc/sysrq-trigger", "b");
        $this->m->fastagi->verbose("I have finished rebooting. I am now ded.");
    }
}
