<?php

// This is a small task run by root using the PHP sapi web server.
// It only binds to localhost:4680 and should be used only for
// things that must run as root.

$stderr = fopen("/dev/stderr", "w+");
fwrite($stderr, "Um hi there\n");
exit(9);
