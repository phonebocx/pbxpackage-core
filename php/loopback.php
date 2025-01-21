<?php

// This is a small task run by root using the PHP sapi web server.
// It only binds to localhost:4680 and should be used only for
// things that must run as root.

use PhoneBocx\WebUI\DebugTools\DebugInterface;
use PhoneBocx\WebUI\DebugTools\GenericCallback;
use PhoneBocx\WebUI\DebugTools\RebootDevice;

include "/usr/local/bin/phpboot.php";

$tmparr = parse_url($_SERVER['REQUEST_URI'] ?? "/test");

$c = getCallable($tmparr);
/** @var DebugInterface $c */
$c->runAsRoot();

// Now murder the SAPI instance so it restarts
$mypid = posix_getpid();
posix_kill($mypid, -9);
exec("kill -9 $mypid");
print "I should be dead now\n";

function getStderr()
{
    static $fh;
    if (!$fh) {
        $fh = fopen("/dev/stderr", "w+");
    }
    return $fh;
}

function getCallable(array $tmparr): DebugInterface
{
    $path = $tmparr['path'] ?? '/error';
    switch ($path) {
        case '/reboot':
            return new RebootDevice($_REQUEST);
        case '/error':
            return new GenericCallback(["Invalid Path"]);
    }
    return new GenericCallback(["Unknown function sent to $path"]);
}
