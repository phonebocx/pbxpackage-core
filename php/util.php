#!/usr/bin/env php
<?php

use PhoneBocx\Commands;

include "/usr/local/bin/phpboot.php";

$params = Commands::getCommands();

$gopts = [];
$help = [];
foreach ($params as $k => $v) {
    $gopts[] = "$k::";
    $help[$k] = $v['help'];
}
$opts = getopt('', $gopts);

if (empty($opts)) {
    print "Usage:\n";
    foreach ($help as $k => $v) {
        print "  $k - $v\n";
    }
    exit;
}

$funcs = [];

foreach ($opts as $o => $p) {
    $v = $params[$o];
    $arr = ["callable" => $v['callable'], "params" => $p, "print" => $v['print'] ?? false];
    if (isset($params[$o]['priority'])) {
        $funcs["00.$o"] = $arr;
    } else {
        $funcs[$o] = $arr;
    }
}

ksort($funcs);

foreach ($funcs as $o => $v) {
    $f = $v['callable'];
    $p = $v['params'];
    $r = $f($p);
    if ($v['print']) {
        print $r;
    }
}
