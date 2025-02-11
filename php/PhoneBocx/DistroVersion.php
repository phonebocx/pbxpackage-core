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

    private static array $distrovars = [
        "kernelver" => "2.4.24", // This is used to see if it needs to be reloaded
        "buildver" => "2021.01-001",
        "distroname" =>  "NoDistroVars",
        "shortname" => "UnknownDistro",
        "apiurl" => "https://example.com",
        "baseurl" => "https://example.com",
        "brandname" => "NoBrandName PhoneBo.cx",
        "disturl" => "https://example.com/nlatest",
        "latestiso" => "https://example.com/latest.iso",
        "pkgurl" => "http://phonebo.cx/packages",
        "buildutime" => 1700000000,
        "timestamp" => "Tue Nov 14 10:13:20 PM UTC 2023",
    ];

    public static function getDistroVars(bool $refresh = false, string $filename = "/distro/distrovars.json"): array
    {
        $retarr = self::$distrovars;
        if ($retarr['kernelver'] === "2.4.24" || $refresh) {
            if (file_exists($filename)) {
                $j = json_decode(file_get_contents($filename), true);
                foreach ($j as $k => $v) {
                    $retarr[$k] = $v;
                }
            }
        }
        return $retarr;
    }

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
                $dv = self::getDistroVars();
                self::$shortname = $dv['shortname'] ?? "Generic";
            } else {
                self::$shortname = trim(file_get_contents("/distro/shortname"));
            }
        }
        return self::$shortname;
    }
}
