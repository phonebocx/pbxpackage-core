<?php

namespace PhoneBocx;

use PhoneBocx\Console\Window2;
use PhoneBocx\Console\Window3;
use PhoneBocx\Models\HookModel;

class Console
{
    public static function go(string $param = "")
    {
        $hooks = PhoneBocx::create()->triggerHook("console", ["param" => $param]);
        // If the hook model has been set with an override, call that.
        $model = $hooks['__model'] ?? null;
        /** @var HookModel $model */
        if ($model) {
            // This should always exist, but assume bugs!
            if ($model->hasCallable()) {
                $retarr = [];
                foreach ($model->getCallables() as $c) {
                    if (!is_callable($c)) {
                        $retarr[] = "I was asked to call $c, but it's not callable";
                    } else {
                        foreach ($c($model) as $row) {
                            $retarr[] = $row;
                        }
                    }
                }
                return join("\n", $retarr) . "\n";
            }
        }
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
