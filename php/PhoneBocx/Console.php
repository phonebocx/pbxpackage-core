<?php

namespace PhoneBocx;

use PhoneBocx\Console\Window2;
use PhoneBocx\Console\Window3;

class Console
{
    public static function go(string $param = "")
    {
        switch ($param) {
            case "win2":
                return Window2::trigger();
            case "win3":
                return Window3::trigger();
        }
        print "I don't know about '$param'\n";
        var_dump($param);
    }

    public static function ansiBold(): string
    {
        // ^[[1m
        return hex2bin('1b5b316d');
    }

    public static function ansiReset(): string
    {
        // ^[(B^[[m
        return hex2bin('1b28421b5b6d');
    }
}
