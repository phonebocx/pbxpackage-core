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
$paramhelp = [];
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
    if (!empty($v['extraparams'])) {
        foreach ($v['extraparams'] as $p => $phelp) {
            $gopts[] = "$p::";
            $paramhelp[$k][$p] = $phelp;
        }
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
    renderHelp(["help" => "This help"], $paramhelp);
    renderHelp($help, $paramhelp);
    if ($showfullhelp) {
        renderHelp($hidden, $paramhelp);
    }
    renderHelp(["fullhelp" => "Show hidden commands (should not be used, for internal/testing)"], $paramhelp);
    exit;
}

$funcs = [];

foreach ($opts as $o => $p) {
    // Could be an extra
    if (empty($params[$o])) {
        continue;
    }
    $v = $params[$o];
    $arr = ["callable" => $v['callable'], "params" => $p, "print" => $v['print'] ?? false, 'allopts' => false];
    if (!empty($paramhelp[$o])) {
        $arr['allopts'] = $opts;
    }
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
    if ($v['allopts']) {
        $r = $f($v['allopts'], $argv);
    } else {
        $r = $f($p, $argv);
    }
    if ($v['print']) {
        print $r;
    }
}


function renderHelp(array $helparr, array $paramhelp)
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
        $phelp = $paramhelp[$k] ?? [];
        if ($phelp) {
            print "    $k options:\n";
            foreach ($phelp as $p => $v) {
                print "      --$p:\t$v\n";
            }
        }
        if (!empty($examples[$k])) {
            print "\t\t" . $examples[$k] . "\n";
        }
    }
}
