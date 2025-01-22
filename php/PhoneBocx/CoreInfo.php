<?php

namespace PhoneBocx;

class CoreInfo
{
    private static $settings;
    private static $distfile = "/var/run/phonebocx/latest.dist";

    // These should match bootstrap.inc
    private static $conflabel = '7680d283ce83';
    private static $recoverylabel = 'recovery';

    private static function getSettings($refresh = false)
    {
        if ($refresh || !self::$settings) {
            $g = PhoneBocx::create();
            self::$settings = $g->getSettings();
        }
        return self::$settings;
    }

    public static function getSysId()
    {
        $s = self::getSettings();
        return $s['systemid'] ?? false;
    }

    public static function getSysName()
    {
        $s = self::getSettings();
        return $s['servicename'] ?? "Error";
    }

    public static function getServices()
    {
        $s = self::getSettings();
        $stat = $s['faxservice'] ?? "Unknown";
        return ["Fax Service" => $stat];
    }

    public static function getKernelVersion()
    {
        $ver = trim(file_get_contents("/proc/version"));
        $retarr = ["kver" => "unknown", "kbuild" => "unknown"];
        if (preg_match("/^Linux version ([^\s]+)/", $ver, $out)) {
            $retarr['kver'] = $out[1];
        }
        if (preg_match("/SMP (?:PREEMPT_DYNAMIC )?(.+)$/", $ver, $out)) {
            $retarr['kbuild'] = $out[1];
        }
        return $retarr;
    }

    public static function getInterfaceInfo()
    {
        $interfaces = glob("/sys/class/net/*");
        $retarr = [];
        foreach ($interfaces as $path) {
            $name = basename($path);
            if ($name == "lo" || $name == "wg0") {
                continue;
            }
            $retarr[$name] = self::getIntInfo($name);
        }
        return $retarr;
    }

    public static function getIntInfo($intname)
    {
        static $json;
        if (!$json) {
            exec("/usr/sbin/ip --json addr", $out, $ret);
            if ($ret) {
                $retarr['error'] = [$out, $ret];
                return $retarr;
            }
            $json = [];
            foreach (json_decode($out[0], true) as $i) {
                $iname = $i['ifname'];
                $json[$iname] = $i;
            }
        }
        return $json[$intname] ?? [];
    }

    public static function getSerialNo()
    {
        $sn = "/sys/class/dmi/id/product_serial";
        if (!file_exists($sn)) {
            return "SN-ERROR-DMI";
        }
        if (!is_readable($sn)) {
            return "SN-ERROR-UNREADABLE";
        }
        return trim(file_get_contents($sn));
    }

    public static function getRunningDist($vfile = "/distro/distrovars.json")
    {
        if (!file_exists($vfile)) {
            throw new \Exception("$vfile does not exist");
        } else {
            $distro = json_decode(file_get_contents($vfile), true);
        }
        $retarr = [
            "version" => $distro['kernelver'],
            "utime" => $distro['buildutime'],
            "fullbuild" => $distro['buildver'],
            "build" => "Build",
            "rel" => "rel"
        ];
        return $retarr;
    }

    public static function getLatestUrl()
    {
        $settings = self::getSettings();
        $params = [
            "sysid" => $settings['systemid'] ?? "ERR",
            "serial" => $settings['serial'] ?? "ERR",
            "eid" => $settings['eid'] ?? "ERR",
            "osbuild" => $settings['os_build'] ?? "Normal",
            "devmode" => $settings['devmode'] ?? null,
            "osversion" => "v3",
        ];
        return FileLocations::getDistUrl() . "/nlatest?" . http_build_query($params);
    }

    public static function getLatestDist($refresh = false)
    {
        if (!file_exists(self::$distfile)) {
            $refresh = true;
        } else {
            $maxage = time() - 600;
            $tmparr = stat(self::$distfile);
            if ($tmparr['mtime'] < $maxage) {
                $refresh = true;
            }
        }

        if ($refresh) {
            if (PhoneBocx::safeGet(self::$distfile, self::getLatestUrl(), false)) {
                $disturl = trim(file_get_contents(self::$distfile));
                $maps = [
                    self::$distfile . ".meta" => "$disturl.meta",
                    self::$distfile . ".sha256" => "$disturl.sha256",
                ];
                foreach ($maps as $dest => $url) {
                    PhoneBocx::safeGet($dest, $url, false);
                }
            }
        }
        $retarr = ["meta" => [], "sha256" => "", "url" => ""];
        if (!file_exists(self::$distfile . ".meta") || !file_exists(self::$distfile . ".sha256")) {
            return $retarr;
        }
        $retarr["meta"] = parse_ini_file(self::$distfile . ".meta", true, INI_SCANNER_RAW);
        $retarr["sha256"] = trim(file_get_contents(self::$distfile . ".sha256"));
        $retarr["url"] = trim(file_get_contents(self::$distfile));
        return $retarr;
    }

    public static function getUptime($asstring = false)
    {
        $p = trim(file_get_contents("/proc/uptime"));
        $uptime = explode(" ", $p);
        if (!$asstring) {
            return $uptime[0];
        }
        $utime = floor(time() - $uptime[0]);
        return PeriodText::toStr($utime);
    }

    public static function getQueueCount()
    {
        $queue = Queue::create();
        return $queue->count();
    }

    private static function getVolByLabel(string $label): ?string
    {
        $path = "/dev/disk/by-label/$label";
        if (!file_exists($path)) {
            return null;
        }
        return realpath($path);
    }

    public static function getConfVol(?string $forcelabel = null): ?string
    {
        $label = $forcelabel ?? self::$conflabel;
        return self::getVolByLabel($label);
    }

    public static function getRecoveryVol(?string $forcelabel = null): ?string
    {
        $label = $forcelabel ?? self::$recoverylabel;
        return self::getVolByLabel($label);
    }
}
