<?php

namespace PhoneBocx;

class DistroVersion
{
    private static ?string $shortname = null;
    private static ?array $djson = null;
    private static array $defaults = [
        "commit" => "unknown",
        "utime" => "0",
        "descr" => "no distrovers",
        "shortname" => "Generic",
        "buildenv" => [
            "BUILDUTIME" => "1729461736",
            "BUILD" => "2024.10-014",
            "THEME" => "default",
            "KFIRMWARE" =>  "20240610",
            "BRANCH" => "2024.10",
            "KERNELVER" => "6.6.48",
            "KERNELREL" => "1"
        ],
        "packages" => [],
    ];

    public static function getJson(bool $refresh = false): array
    {
        if (self::$djson === null || $refresh) {
            $dvfile = "/distro/buildinfo.json";
            if (!file_exists($dvfile)) {
                self::$djson = null;
                return self::$defaults;
            }
            $binfo = json_decode(file_get_contents($dvfile), true);
            if (!is_array($binfo)) {
                self::$djson = null;
                return self::$defaults;
            }
            self::$djson = $binfo;
        }
        return self::$djson;
    }

    public static function getShortname(): string
    {
        if (self::$shortname === null) {
            if (!file_exists("/distro/shortname")) {
                return "Generic";
            }
            self::$shortname = trim(file_get_contents("/distro/shortname"));
        }
        return self::$shortname;
    }
}
