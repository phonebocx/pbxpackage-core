<?php

use GoldLinux\API\Sysinfo;

include __DIR__ . '/../php/boot.php';

$uriarr = parse_url($_SERVER['REQUEST_URI']);
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
    $p = new Sysinfo();
    return $p->respond();
}
