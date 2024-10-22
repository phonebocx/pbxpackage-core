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
}
