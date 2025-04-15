<?php

namespace PhoneBocx;

class Dahdi
{
    /**
     * Location to keep the cached result of dahdi_scan
     *
     * @return string
     */
    public static function getDahdiScanJson(): string
    {
        return PhoneBocx::getBaseDir() . "/dahdi_scan.json";
    }

    /**
     * Used by Commands - 'util --getdahdiscan' and API.php
     *
     * @param string|null $param
     * @param bool $print Actually print (disabled in API.php)
     * @return string
     */
    public static function getDahdiScanCmd(?string $param = "", bool $print = true): string
    {
        if ($param) {
            $refresh = true;
        } else {
            $refresh = false;
        }
        $scan = self::getDahdiScan($refresh);
        $res = $scan['str'] ?? "";
        if ($print) {
            print $res;
        }
        return $res;
    }

    /**
     * Run and cache (if possible) the result of dahdi_scan
     *
     * @param boolean $refresh
     * @return array
     */
    public static function getDahdiScan(bool $refresh = false): array
    {
        $scanfile = self::getDahdiScanJson();
        if ($refresh) {
            @unlink($scanfile);
        }
        if (!file_exists($scanfile)) {
            if (!file_exists("/dev/dahdi/ctl")) {
                return [];
            }
            exec("/usr/sbin/dahdi_scan", $out, $ret);
            if ($ret != 0) {
                throw new \Exception("Could not run dahdiscan, ret was $ret from " . json_encode($out));
            }
            $str = join("\n", $out);
            $j = ["out" => $out, "str" => $str, "scanfile" => $scanfile];
            $parsed = parse_ini_string($str, true);
            $j['parsed'] = $parsed;
            file_put_contents($scanfile, json_encode($j));
            chmod($scanfile, 0777);
        } else {
            $j = json_decode(file_get_contents($scanfile), true);
        }
        return $j;
    }

    public static function getDahdiStr(bool $refresh = false)
    {
        $j = self::getDahdiScan($refresh);
        return $j['str'] ?? "";
    }
}
