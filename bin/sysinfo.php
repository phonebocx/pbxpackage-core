#!/usr/bin/php
<?php

use PhoneBocx\FileLocations;
use PhoneBocx\PhoneBocx;

include __DIR__ . "/../php/boot.php";

$opts = getopt("sdjif");

$pbx = PhoneBocx::create();

if (isset($opts['s'])) {
    $action = "set";
} elseif (isset($opts['d'])) {
    $action = "delete";
} elseif (isset($opts['j'])) {
    $action = "jsondump";
} elseif (isset($opts['i'])) {
    $action = "inifile";
} elseif (isset($opts['f'])) {
    // Print the sqlite file we are currently using
    print $pbx->getDbFilename();
    exit;
} else {
    $action = "get";
}

if ($action == "jsondump") {
    print json_encode($pbx->getSettings());
    exit;
}
if ($action == "inifile") {
    $ini = ["# Ini generated at " . time()];
    foreach ($pbx->getSettings() as $k => $v) {
        $ini[] = "$k=" . escapeshellarg($v);
    }
    file_put_contents(FileLocations::getIniFileLocation(), join("\n", $ini));
    exit;
}

if ($action == "get") {
    $key = $argv[2] ?? $argv[1] ?? "";
} else {
    $key = $argv[2];
}

if (!$key) {
    print "BUG-NO-KEY-PROVIDED";
    exit;
}

$current = $pbx->getKey($key);

if ($action == "get") {
    print $current;
    exit;
}

if ($action == "delete") {
    if ($current) {
        $pbx->setKey($key, false);
        print "# DELETED $key\n";
    }
    exit;
}

if ($action == "set") {
    $val = $argv[3] ?? false;
    if (!$val) {
        throw new \Exception("Cannot set a blank val, use delete");
    }
    if ($current !== $val) {
        $pbx->setKey($key, $val);
        print "# UPDATED $key=$val\n";
    }
    exit;
}
