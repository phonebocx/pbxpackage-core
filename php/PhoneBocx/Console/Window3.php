<?php

namespace PhoneBocx\Console;

use PhoneBocx\Console;
use PhoneBocx\DistroVersion;
use PhoneBocx\PhoneBocx;

class Window3
{
    public static function trigger(): string
    {
        $name = DistroVersion::getShortname();
        // This is \DATE_RFC822 with 'UTC' instead of '+0000'
        $human = 'D M d, H:i:s T Y';
        $s = (new \DateTime())->format($human);
        $retarr = ["Current Timestamp: $s", "", "Last response from $name:"];
        $ts = PhoneBocx::create()->getKey('servicetimestamp');
        $age = time() - $ts;
        if ($age > 300) {
            $retarr[] = "  More than 5 minutes ago!";
        } else {
            $retarr[] = "  $age seconds ago";
        }
        $retarr[] = "";
        $sstatus = PhoneBocx::create()->getKey('servicestatus');
        $retarr[] = "$name Status:";
        $retarr[] = "  " . Console::ansiBold() . "$sstatus" . Console::ansiReset();
        return join("\n", $retarr) . "\n";
    }
}
