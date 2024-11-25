#!/usr/bin/env php
<?php

use PhoneBocx\Commands;

if (!file_exists("/usr/local/bin/phpboot.php")) {
    // Hasn't finished booting
    exit;
}

include "/usr/local/bin/phpboot.php";

$params = Commands::getCommands();

$gopts = ["fullhelp", "help"];
$help = [];
$hidden = [];
$examples = [];
foreach ($params as $k => $v) {
    $gopts[] = "$k::";
    if (!empty($v['hide'])) {
        $hidden[$k] = $v['help'];
    } else {
        $help[$k] = $v['help'];
    }
    if (!empty($v['example'])) {
        $examples[$k] = $v['example'];
    }
}

$opts = getopt('', $gopts);
$showhelp = false;
$showfullhelp = false;

if (empty($opts) || array_key_exists('help', $opts)) {
    $showhelp = true;
}
if (array_key_exists('fullhelp', $opts)) {
    $showhelp = true;
    $showfullhelp = true;
}

if ($showhelp) {
    print "Usage:\n";
    renderHelp(["help" => "This help"]);
    renderHelp($help);
    if ($showfullhelp) {
        renderHelp($hidden);
    }
    renderHelp(["fullhelp" => "Show hidden commands (should not be used, for internal/testing)"]);
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


function renderHelp(array $helparr)
{
    global $examples;
    foreach ($helparr as $k => $v) {
        // If the key is longer than 12 chars, display the
        // help on the next line.
        if (strlen($k) > 10) {
            print "  --$k:\n\t\t$v\n";
        } else {
            print "  --$k:\t$v\n";
        }
        if (!empty($examples[$k])) {
            print "\t\t" . $examples[$k] . "\n";
        }
    }
}
