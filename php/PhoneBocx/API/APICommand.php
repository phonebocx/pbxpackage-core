<?php

namespace PhoneBocx\API;

use PhoneBocx\Logs;

class APICommand
{
  public static function handle($a)
  {
    if (posix_getuid() !== 0) {
      Logs::addLogEntry('Not running as root - am ' . posix_getuid());
    }
    $contents = $a['contents'];
    $cmds = [];
    foreach ($contents as $row) {
      if (isset($row['cmd'])) {
        $cmds[] = $row['cmd'];
      }
      if (isset($row['encodedcmd'])) {
        $cmds[] = base64_decode($row['encodedcmd']);
      }
    }
    foreach ($cmds as $c) {
      $out = [];
      $ret = 99;
      exec($c, $out, $ret);
      Logs::addLogEntry("Remote API Command '$c' return code '$ret'", "System", $out);
    }
  }
}
