#!/usr/bin/env php
<?php

use PhoneBocx\API;
use PhoneBocx\Dahdi;
use PhoneBocx\Logs;
use PhoneBocx\PhoneBocx;

include __DIR__ . "/boot.php";

$a = new API();
var_dump($a->getApiParams());
exit;
var_dump(Logs::getLogs());
Logs::addLogEntry('test message');
var_dump(Logs::getLogs());
exit;
$pb = PhoneBocx::create();
var_dump($pb->triggerHook("boot"));
var_dump($pb->triggerHook("boot"));
var_dump($pb->triggerHook("boot"));
var_dump($pb->triggerHook("fboot"));
