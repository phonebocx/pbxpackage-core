<?php

namespace PhoneBocx\Services;

use PhoneBocx\PhoneBocx;

abstract class ServiceAbstraction
{
    protected PhoneBocx $pbx;

    public function __construct(PhoneBocx $pbx)
    {
        $this->pbx = $pbx;
    }

    public function isCurrentlyRunning(): bool
    {
        return false;
    }
}
