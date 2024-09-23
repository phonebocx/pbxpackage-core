<?php

namespace PhoneBocx;

/** @package PhoneBocx */
class FileLocations
{
    private static $baseurl = "http://repo.phonebo.cx";
    private static $dbfiles = ["/spool" => "/spool/data/base.sq3", "/var/run" => "/var/run/phonebocx/base.sq3"];

    public static function getProdDbFilename()
    {
        return self::$dbfiles["/spool"];
    }
    public static function getDbFiles()
    {
        return self::$dbfiles;
    }

    public static function getBaseUrl()
    {
        return self::$baseurl;
    }

    public static function getIniFileLocation()
    {
        return "/var/run/phonebocx/sysinfo.ini";
    }
}
