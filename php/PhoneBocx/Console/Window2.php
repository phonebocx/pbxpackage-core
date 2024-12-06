<?php

namespace PhoneBocx\Console;

class Window2
{
    public static function trigger(): string
    {
        // This is \DATE_RFC822 with 'UTC' instead of '+0000'
        $human = 'D M d, H:i:s T Y';
        $s = (new \DateTime())->format($human);
        $retarr = ["Window 2 Current Timestamp: $s", ""];
        $retarr[] = "This can be used for extra information";
        return join("\n", $retarr) . "\n";
    }
}
