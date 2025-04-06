<?php

namespace PhoneBocx;

use PhoneBocx\Commands\QueueUtils;
use PhoneBocx\Console\WindowHeader;
use PhoneBocx\Services\JobSystemdService;

class Commands
{
    public static function getCommands()
    {
        $commands = [
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
                "help" => "Show all sysinfo vals. Output is json, use jq, or set param to ini to generate /var/run/disto/sysinfo.ini",
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
            "pkgdump" => [
                "help" => "Dump all package info (remote and local)",
                "callable" => self::class . "::pkgDump",
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
                "example" => "--pkgneedsupdate core",
                "print" => true,
            ],
            "pkgdownload" => [
                "help" => "Download the package",
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
            "queuestats" => [
                "help" => "Get job queue stats",
                "callable" => QueueUtils::class . "::getSummary",
                "print" => true,
            ],
            "winheader" => [
                "help" => "Display the header for a window",
                "callable" => self::class . "::getWindowHeader",
                "example" => "--winheader=win1",
                "print" => true,
            ]
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

    public static function getAllSysInfoVal($param = "")
    {
        PhoneBocx::checkDbStructure();
        $pb = PhoneBocx::create();
        $all = $pb->getSettings();
        unset($all['logarr']);
        if (!$param) {
            return json_encode($all);
        }
        if ($param == "ini") {
            $dest = "/var/run/distro/sysinfo.ini";
        } else {
            return "No support for writing ini files elsewhere, including '$param' Sorry\n";
        }
        $comparison = $all;
        // Skip any keys we don't care about comparing
        foreach (array_keys($comparison) as $k) {
            if (strpos($k, "lastrun") === 0) {
                unset($comparison[$k]);
            }
        }
        unset($comparison['vpninfo']);
        ksort($comparison);
        $destdir = dirname($dest);
        if (!is_dir($destdir)) {
            // How?
            mkdir($destdir, 0777, true);
        }
        if (file_exists($dest)) {
            $currentini = file($dest, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);
            $lastline = array_pop($currentini);
            $tmparr = explode(" ", $lastline);
            $checksum = $tmparr[1] ?? "_missing_";
        } else {
            $currentini = [];
            $checksum = "_notpresent_";
        }
        $j = json_encode($comparison);
        $jchecksum = hash('sha256', $j);
        if ($jchecksum === $checksum) {
            return;
        }
        $inibody = ["# sysinfo.ini created by getAllSysInfoVal - do not consume!", "exit 42"];
        foreach ($all as $k => $v) {
            if (is_array($v)) {
                $inibody[] = "$k='" . json_encode($v) . "'";
            } else {
                $inibody[] = "$k=$v";
            }
        }
        $inibody[] = "# $jchecksum - was $checksum";
        file_put_contents($dest, join("\n", $inibody) . "\n");
        return;
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

    public static function pkgDump()
    {
        $remotejson = json_decode(Packages::getCurrentJson(), true);
        $retarr = ["remote" => ["json" => $remotejson, "packages" => []], "local" => []];
        $packages = Packages::getRemotePackages();
        foreach ($packages as $p) {
            $rarr = Packages::remotePkgInfo($p, true);
            $rstr = Packages::remotePkgInfo($p, false);
            $retarr['remote']['packages'][$p] = ["info" => $rstr, "infoarr" => $rarr];
        }
        foreach (Packages::getLocalPackages() as $p => $loc) {
            $larr = Packages::localPkgInfo($p, true);
            $lstr = Packages::localPkgInfo($p, false);
            $retarr['local'][$p] = ["loc" => $loc, "info" => $lstr, "infoarr" => $larr];
        }
        return json_encode($retarr);
    }

    public static function remotePkgList(?string $format = null)
    {
        $retarr = [];
        $packages = Packages::getRemotePackages();
        if ($format == "json") {
            foreach ($packages as $p) {
                $retarr[$p] = Packages::remotePkgInfo($p, true);
            }
            return json_encode($retarr);
        } elseif ($format == "verbose") {
            foreach ($packages as $p) {
                $retarr[] = str_pad("$p", 12) . " " . Packages::remotePkgInfo($p);
            }
            return join("\n", $retarr) . "\n";
        } else {
            return join(" ", $packages);
        }
    }

    public static function checkPkgUpdate(string $pkgname = "", ?array $argv = null)
    {
        if (!$pkgname) {
            // --pkgneedsupdate core
            $pkgname = array_pop($argv);
        }
        if (Packages::doesPkgNeedUpdate($pkgname)) {
            return "true";
        }
        return "";
    }

    public static function downloadPkg(array $params)
    {
        $force = array_key_exists('forcedownload', $params);
        $force = true;
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

    public static function getWindowHeader(string $win, array $params)
    {
        $window = $win;
        $params["processdefault"] = true;
        $params["sysidrequired"] = DistroVars::isSysIdRequired();
        $hookfunc = "windowheader-$window";
        // If something has registered a hook, trigger it. It can then
        // set 'processdefault' to false if it has already output what
        // it needed to output.
        $x = PhoneBocx::create()->triggerHook($hookfunc, ["params" => $params]);
        $params = $x['__model']->params;
        if ($params["processdefault"]) {
            $wh = new WindowHeader($win, $params);
            print $wh->go();
        }
    }
}
