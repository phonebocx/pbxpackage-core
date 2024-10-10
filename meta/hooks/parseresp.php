#!/usr/bin/env php
<?php

use PhoneBocx\ParseApiResp;

include __DIR__ . '/../../php/vendor/autoload.php';

$opts = getopt('f:');
if (empty($opts)) {
    print "Param: -f filename\n";
    exit;
}

$f = $opts['f'];
if (!file_exists($f)) {
    print "$f is not a file\n";
    exit;
}

$a = new ParseApiResp($f);
$a->go();
