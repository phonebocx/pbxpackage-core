<?php

namespace PhoneBocx;

class Dahdi
{
    public static function getDahdiScan(bool $refresh = false): array
    {
        $scanfile = PhoneBocx::getBaseDir() . "/dahdi_scan.json";
        if ($refresh) {
            @unlink($scanfile);
        }
        if (!file_exists($scanfile)) {
            if (!file_exists("/dev/dahdi/ctl")) {
                return [];
            }
            exec("/usr/sbin/dahdi_scan", $out, $ret);
            if ($ret != 0) {
                throw new \Exception("Could lot run dahdiscan, ret was $ret from " . json_encode($out));
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
