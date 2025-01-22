#!/usr/bin/env php
<?php

use PhoneBocx\API;
use PhoneBocx\AsRoot\RemountConf;
use PhoneBocx\Dahdi;
use PhoneBocx\Logs;
use PhoneBocx\PhoneBocx;

include __DIR__ . "/boot.php";

$c = new RemountConf();
var_dump($c->isReadWrite());
var_dump($c->mountRw());
var_dump($c->isReadWrite());
var_dump($c->mountRo());
var_dump($c->isReadWrite());
exit;
$p = PhoneBocx::create();
$s = $p->getServiceMgr();
var_dump($s->runNextTask());
exit;

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
