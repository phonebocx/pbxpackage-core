<?php

// This is a small task run by root using the PHP sapi web server.
// It only binds to localhost:4680 and should be used only for
// things that must run as root.

require __DIR__ . "/vendor/autoload.php";

$tmparr = parse_url($_SERVER['REQUEST_URI'] ?? "/test");

$c = getCallable($tmparr);
if (!is_callable($c)) {
    print "Not callable when asking for " . json_encode($tmparr) . "\n";
    exit(1);
}
$c();

function getStderr()
{
    static $fh;
    if (!$fh) {
        $fh = fopen("/dev/stderr", "w+");
    }
    return $fh;
}

function getCallable(array $tmparr)
{
    $path = $tmparr['path'] ?? '/error';
    switch ($path) {
        case '/error':
            return function () {
                print "Invalid path";
            };
    }
    return function () {
        print "Unknown function\n";
    };
}
