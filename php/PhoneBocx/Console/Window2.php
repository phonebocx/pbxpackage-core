<?php

namespace PhoneBocx\Console;

use PhoneBocx\PhoneBocx;

class Window2
{
    public static function trigger(): string
    {
        // This is \DATE_RFC822 with 'UTC' instead of '+0000'
        $human = 'D M d, H:i:s T Y';
        $s = (new \DateTime())->format($human);
        $retarr = ["Window 2 Current Timestamp: $s", ""];
        $p = PhoneBocx::create();
        $s = $p->getServiceMgr();
        $z = $s->runNextTask();
        $retarr[] = "Count of z is " . count($z);
        foreach ($z as $r) {
            $retarr[] = $r;
        }
        $retarr[] = "";
        $slogs = $s->getTaskLogs();
        foreach ($slogs as $u => $v) {
            $retarr[] = "Utime $u - " . $v[0];
        }
        return join("\n", $retarr) . "\n";
    }
}
