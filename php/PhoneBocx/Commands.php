<?php

namespace PhoneBocx;

use PhoneBocx\Services\JobSystemdService;

class Commands
{
    public static function getCommands()
    {
        $commands = [
            "checksysinfo" => [
                "help" => "Checks the sysinfo table.",
                "callable" => self::class . "::checkSysInfoDb",
                "priority" => true
            ],
            "pkgdisplay" => [
                "help" => "Display currently installed packages",
                "callable" => self::class . "::showLocalPackages",
                "print" => true
            ],
            "getsysinfo" => [
                "help" => "Get a sysinfo val",
                "callable" => self::class . "::getSysInfoVal",
                "print" => true
            ],
            "allsysinfo" => [
                "help" => "Show all sysinfo vals. Output is json, use jq",
                "callable" => self::class . "::getAllSysInfoVal",
                "print" => true
            ],
            "disturl" => [
                "help" => "Get the URL to check for the latest ISO",
                "callable" => self::class . "::getDistURL",
                "print" => true
            ],
            "pkgurl" => [
                "help" => "Get the URL to check for package updates",
                "callable" => self::class . "::getPkgURL",
                "print" => true
            ],
            "parsedahdiscan" => [
                "help" => "Parse the output of get_dahdi_scan",
                "callable" => PortStatus::class . "::parseDahdiScanStdin",
                "print" => true
            ],
            "parseresp" => [
                "help" => "Parse API Response",
                "callable" => ParseApiResp::class . "::launchFromFile",
                "print" => true
            ],
            "showlogs" => [
                "help" => "Show last 30 (or specified) logs",
                "example" => "--showlogs=10 will show the last 10 logs",
                "callable" => self::class . "::showLogs",
                "print" => true
            ],
            "console" => [
                "help" => "Display output for a console window",
                "example" => "--console=win2 displays the output for window2",
                "callable" => Console::class . "::go",
                "print" => true,
                "hide" => true
            ],
            "jobservice" => [
                "help" => "This is the systemd job service, run by systemd.",
                "callable" => JobSystemdService::class . "::launch",
                "print" => true,
                "hide" => true
            ],
            "pkgjson" => [
                "help" => "Returns the current package.json",
                "callable" => self::class . "::pkgJson",
                "print" => true,
            ],
            "remotepkgs" => [
                "help" => "Returns a list of remote packages",
                "example" => "--remotepkgs=json will output json",
                "callable" => self::class . "::remotePkgList",
                "print" => true,
            ],
            "pkgneedsupdate" => [
                "help" => "Does this package need an update",
                "callable" => self::class . "::checkPkgUpdate",
                "print" => true,
            ],
            "pkgdownload" => [
                "help" => "Does this package need an update",
                "callable" => self::class . "::downloadPkg",
                "print" => true,
                "extraparams" => [
                    "destdir" => "Destination Directory (Mandatory)",
                    "forcedownload" => "Always download, even if they are already here",
                    "pkgoutput" => "Optional style of output",
                ],
            ],
            "checkdownload" => [
                "help" => "Check the downloaded package is valid",
                "callable" => self::class . "::checkPkgHashes",
                "print" => true,
            ],
        ];
        // Important: Pass by ref!
        $params = ["commands" => &$commands];
        PhoneBocx::create()->triggerHook("commands", $params);
        return $commands;
    }

    public static function showLocalPackages($param = "")
    {
        if ($param) {
            $refresh = true;
        } else {
            $refresh = false;
        }
        Packages::$quiet = false;
        return Packages::getPkgDisplay();
    }

    public static function getSysInfoVal($v)
    {
        $pb = PhoneBocx::create();
        return $pb->getKey($v);
    }

    public static function getAllSysInfoVal()
    {
        $pb = PhoneBocx::create();
        $all = $pb->getSettings();
        unset($all['logarr']);
        return json_encode($all);
    }

    public static function checkSysInfoDb()
    {
        PhoneBocx::checkDbStructure();
        $pb = PhoneBocx::create();
        $allkeys = $pb->getSettings();
        return json_encode($allkeys);
    }

    public static function getDistURL()
    {
        return CoreInfo::getLatestUrl();
    }

    public static function getPkgURL()
    {
        return Packages::getFullPkgUrl() . "\n";
    }

    public static function showLogs(?int $count = null)
    {
        if (!$count) {
            $count = 30;
        }
        return join("\n", Logs::getHumanLogs($count)) . "\n";
    }

    public static function pkgJson($param = "")
    {
        if ($param) {
            $refresh = true;
        } else {
            $refresh = false;
        }
        return Packages::getCurrentJson($refresh);
    }

    public static function remotePkgList(?string $format = null)
    {
        $retarr = [];
        $packages = Packages::getRemotePackages();
        if ($format) {
            foreach ($packages as $p) {
                $retarr[$p] = Packages::remotePkgInfo($p, true);
            }
            return json_encode($retarr);
        } else {
            foreach ($packages as $p) {
                $retarr[] = str_pad("$p", 12) . " " . Packages::remotePkgInfo($p);
            }
            return join("\n", $retarr) . "\n";
        }
    }

    public static function checkPkgUpdate(string $pkgname = "")
    {
        if (Packages::doesPkgNeedUpdate($pkgname)) {
            return "true";
        }
        return "";
    }

    public static function downloadPkg(array $params)
    {
        $force = array_key_exists('forcedownload', $params);
        $res = Packages::downloadRemotePackage($params['pkgdownload'], $params['destdir'], $force);
        $outputstyle = $params['pkgoutput'] ?? 'json';
        switch ($outputstyle) {
            case 'filenames':
                return join("\n", array_keys($res)) . "\n";
            case 'json':
                return json_encode($res);
        }
        throw new \Exception("Unknown outputstyle $outputstyle");
    }

    public static function checkPkgHashes(string $pkgbase)
    {
        if (!file_exists($pkgbase)) {
            return "Error: $pkgbase missing";
        }
        $hashfile = $pkgbase . ".sha256";
        if (!file_exists($hashfile)) {
            return "Error: $hashfile missing";
        }
        $shouldbe = file_get_contents($hashfile);
        $localhash = hash_file('sha256', $pkgbase);
        if ($shouldbe !== $localhash) {
            return "Hash mismatch: " . json_encode([$shouldbe, $localhash]);
        }
        return "";
    }
}
