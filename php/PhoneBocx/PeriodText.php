<?php

namespace PhoneBocx;

use DateTime;

class PeriodText
{
    public static $short = false;

    public static function toStr($utime, $skip = [])
    {
        $arr = self::toArr($utime);
        if (!$skip) {
            if ($arr['days']) {
                $skip['secs'] = true;
                $skip['mins'] = true;
            }
            if ($arr['months']) {
                $skip['mins'] = true;
                $skip['hours'] = true;
            }
            if ($arr['years']) {
                $skip = ["secs" => true, "mins" => true, "hours" => true];
            }
        }
        $str = "";
        foreach ($arr as $k => $v) {
            if (!empty($skip[$k])) {
                continue;
            }
            if ($v) {
                if (!self::$short) {
                    $str .= "$v, ";
                } else {
                    $str .= "$v";
                }
            }
        }
        return rtrim($str, ", ");
    }

    public static function toArr($utime)
    {
        $p = function ($str, $v) {
            if (self::$short) {
                $ret = "$v" . strtolower(substr($str, 0, 1));
                return $ret;
            }
            $ret = "$v $str";
            if ($v > 1) {
                return $ret . "s";
            }
            return $ret;
        };

        $di = self::getDateInterval($utime);

        $retarr = ['years' => 0, 'months' => 0, 'days' => 0, 'hours' => 0, 'mins' => 0, 'secs' => 0];
        if ($di->y > 0) {
            $retarr['years'] = $p("Year", $di->y);
        }

        if ($di->m > 0) {
            $retarr['months'] = $p("Month", $di->m);
        }

        if ($di->d > 0) {
            $retarr['days'] = $p("Day", $di->d);
        }

        if ($di->h > 0) {
            $retarr['hours'] = $p("Hour", $di->h);
        }

        if ($di->i > 0) {
            $retarr['mins'] = $p("Minute", $di->i);
        }

        if ($di->s > 0) {
            $retarr['secs'] = $p("Second", $di->s);
        }
        return $retarr;
    }

    public static function getDateInterval($utime)
    {

        $now = new DateTime();
        $then = new DateTime("@$utime");
        return $now->diff($then);
    }
}
