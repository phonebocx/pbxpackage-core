<?php

use PhoneBocx\WebUI\MainPage;

if (!file_exists("/usr/local/bin/phpboot.php")) {
    // Hasn't finished booting
    exit;
}
include "/usr/local/bin/phpboot.php";

$uri = $_SERVER['REQUEST_URI'] ?? "http://example.com/core/api/poll";
$uriarr = parse_url($uri);
$querystr = [];
if (!empty($uriarr['query'])) {
    parse_str($uriarr['query'], $querystr);
}

$cmdarr = explode('/', str_replace('/core/api', '', $uriarr['path']));

$cmd = $cmdarr[1] ?? 'error';

switch ($cmd) {
    case "poll":
        return genPoll();
    default:
        print "Dunno $cmd\n";
}

function genPoll()
{
    $p = new MainPage();
    return $p->respond();
}
