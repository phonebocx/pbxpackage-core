<?php

namespace PhoneBocx;

class Commands
{
    public static function getCommands()
    {
        return [
            "checksysinfo" => ["help" => "Checks the sysinfo table.", "callable" => Commands::class . "::checkSysInfoDb", "priority" => true],
            "pkgdisplay" => ["help" => "Display currently installed packages", "callable" => Commands::class . "::showLocalPackages", "print" => true],
            "getsysinfo" => ["help" => "Get a sysinfo val", "callable" => Commands::class . "::getSysInfoVal", "print" => true],
        ];
    }

    public static function showLocalPackages()
    {
        Packages::$quiet = false;
        return Packages::getPkgDisplay();
    }

    public static function getSysInfoVal($v)
    {
        $pb = PhoneBocx::create();
        return json_encode($pb->getKey($v));
    }

    public static function checkSysInfoDb()
    {
        PhoneBocx::checkDbStructure();
        $pb = PhoneBocx::create();
        $allkeys = $pb->getSettings();
        return json_encode($allkeys);
    }
}
