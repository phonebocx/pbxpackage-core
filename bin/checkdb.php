#!/usr/bin/php
<?php

use PhoneBocx\PhoneBocx;

include __DIR__ . "/../php/boot.php";

if (PhoneBocx::checkDbStructure()) {
    print " * DB Structure OK.\n";
} else {
    print " *** DB Structure FAILED ***\n";
}
