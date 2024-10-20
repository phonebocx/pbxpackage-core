<?php
// I am core/meta/bin/phpboot.php

// This is where everything should start from, as it'll load
// the (hopefully!) correct boot.php to get everything started.

// These are where they could be, in order of preference.
$boots = ["/factory/core/php/boot.php", "/pbxdev/core/php/boot.php", "/pbx/core/php/boot.php"];
$booted = false;
foreach ($boots as $b) {
    if (file_exists($b)) {
        include $b;
        $booted = $b;
        break;
    }
}

if (!$booted) {
    print "Could not boot. System broken.\n";
    exit;
}
// At this point you're good to go. You have a basic autoloader, and if you want
// to pull it any more things, you can do something like:
// $pb = \PhoneBocx\PhoneBocx::boot(["fax", "telephony"]);

// The $booted variable tells you where you booted from, just in case you want
// to do things differently in devmode or whatever.
