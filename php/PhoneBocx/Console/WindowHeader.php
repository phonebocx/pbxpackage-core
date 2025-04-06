<?php

namespace PhoneBocx\Console;

use PhoneBocx\CoreInfo;
use PhoneBocx\DistroVars;

class WindowHeader
{
    private string $name;
    private array $params;

    public function __construct(string $name, array $params)
    {
        $this->name = $name;
        $this->params = $params;
        if (empty($this->params['hookname'])) {
            $this->params['hookname'] = self::class . "::hook_" . $this->name;
        }
    }

    public function go()
    {
        if (is_callable($this->params['hookname'])) {
            return $this->params['hookname']($this->name, $this->params);
        }
        $header = $this->params["header"] ?? "";
        return $header;
    }

    private static function hook_win1($name, $params)
    {
        $sysid = CoreInfo::getSysId();
        $ret = DistroVars::getShortname();
        if (!$sysid) {
            $required = $params['sysidrequired'] ?? true;
            if ($required) {
                $ret .= " - NO SYSTEM ID";
            }
        } else {
            $ret .= " - System ID $sysid";
        }
        $serno = CoreInfo::getSerialNo("Unknown");
        $ret .= " (Device Serial No $serno)";
        return "$ret\n\n";
    }
}
