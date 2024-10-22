<?php

namespace PhoneBocx\Models;

use PhoneBocx\Models\HookModel;

abstract class FastAGIAbstract
{
    public static string $iam = '';

    public static function request(HookModel $m): ?string
    {
        if (!static::$iam) {
            throw new \Exception("iam is not defined!");
        }
        if ($m->pkg !== static::$iam) {
            return null;
        }
        $f = new static($m);
        return $f->go();
    }

    protected HookModel $m;

    public function __construct(HookModel $m)
    {
        $this->m = $m;
    }

    public function go(): ?string
    {
        $cmd = array_shift($this->m->scriptarr);
        if (!$cmd) {
            $cmd = "default";
        }
        $funcname = "agi_$cmd";
        if (method_exists($this, $funcname)) {
            $this->m->fastagi->verbose("I am calling $funcname");
            return $this->$funcname();
        }
        $this->m->fastagi->verbose("I wanted to call $funcname but it did not exist");
        return $funcname;
    }
}
