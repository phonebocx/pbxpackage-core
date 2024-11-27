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
                "callable" => Commands::class . "::checkSysInfoDb",
                "priority" => true
            ],
            "pkgdisplay" => [
                "help" => "Display currently installed packages",
                "callable" => Commands::class . "::showLocalPackages",
                "print" => true
            ],
            "getsysinfo" => [
                "help" => "Get a sysinfo val",
                "callable" => Commands::class . "::getSysInfoVal",
                "print" => true
            ],
            "disturl" => [
                "help" => "Get the URL to check for the latest ISO",
                "callable" => Commands::class . "::getDistURL",
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
                "callable" => Commands::class . "::showLogs",
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
        ];
        // Important: Pass by ref!
        $params = ["commands" => &$commands];
        PhoneBocx::create()->triggerHook("commands", $params);
        return $commands;
    }

    public static function showLocalPackages()
    {
        Packages::$quiet = false;
        return Packages::getPkgDisplay();
    }

    public static function getSysInfoVal($v)
    {
        $pb = PhoneBocx::create();
        return $pb->getKey($v);
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

    public static function showLogs(?int $count = null)
    {
        if (!$count) {
            $count = 30;
        }
        return join("\n", Logs::getHumanLogs($count)) . "\n";
    }
}
