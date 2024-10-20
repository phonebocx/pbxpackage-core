#!/usr/bin/env php
<?php

use PhoneBocx\PhoneBocx;

include __DIR__ . "/boot.php";

$pb = PhoneBocx::create();
var_dump($pb->triggerHook("boot"));
var_dump($pb->triggerHook("boot"));
var_dump($pb->triggerHook("boot"));
var_dump($pb->triggerHook("fboot"));
