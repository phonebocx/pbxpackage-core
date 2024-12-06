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

    public static function launchFromCommand(): string
    {
        $retarr = [];
        /** @var ServiceInterface $s */
        $s = new static(PhoneBocx::create());
        if ($s->isCurrentlyRunning()) {
            return "Service Already Running, try again later\n";
        }
        try {
            if (!$s->launch($retarr)) {
                throw new \Exception("Launch failed");
            }
        } catch (\Exception $e) {
            return "Error launching task - " . $e->getMessage() . "\n";
        }
        return join("\n", $retarr);
    }

    public function isCurrentlyRunning(): bool
    {
        return false;
    }
}
