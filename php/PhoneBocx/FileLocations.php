<?php

namespace PhoneBocx;

/**
 * Note that URL locations are in DistroVars
 *
 * @package PhoneBocx
 */
class FileLocations
{
    private static $dbfiles = ["/spool" => "/spool/data/base.sq3", "/var/run" => "/var/run/phonebocx/base.sq3"];

    public static function getProdDbFilename()
    {
        return self::$dbfiles["/spool"];
    }

    public static function getDbFiles()
    {
        return self::$dbfiles;
    }

    public static function getIniFileLocation()
    {
        return "/var/run/phonebocx/sysinfo.ini";
    }
}
