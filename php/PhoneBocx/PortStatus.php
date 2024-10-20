<?php

namespace PhoneBocx;

class PortStatus
{
  public static function parseDahdiScanFromFile($file)
  {
    $dahdiscan = [];
    while ($line = fgets($file)) {
      if (preg_match('/port=(\d+),(.+)$/', $line, $out)) {
        $port = $out[1];
        $type = $out[2];
        $dahdiscan[$port] = ["type" => $type];
      }
    }
    return self::parseDahdiScan($dahdiscan);
  }

  public static function getCachedDahdiScan($file = "/var/run/distro/dahdi_scan")
  {
    if (!file_exists($file)) {
      return [];
    }
    $fh = fopen($file, "r");
    return self::parseDahdiScanFromFile($fh);
  }

  public static function parseDahdiScanStdin()
  {
    $ds = self::parseDahdiScanFromFile(STDIN);
    foreach ($ds as $port => $data) {
      print "  $port: " . $data['type'] . $data['error'] . " ";
      if ($data['active'] != 1) {
        print "Disabled\n";
        continue;
      }
      print "Active: " . $data['name'] . " (+" . $data['cid'] . ")\n";
      //  Dest: " . $data['dest'] . "\n";
    }
  }

  public static function parseDahdiScan($dahdiscan)
  {
    // If we didn't get anything then don't bother continuing
    if (!$dahdiscan) {
      return;
    }

    $sysinfo = PhoneBocx::create()->getSettings();
    foreach ($dahdiscan as $port => $data) {
      $dahdiscan[$port]['active'] = $sysinfo["port{$port}active"] ?? "error";
      $dahdiscan[$port]['cid'] = $sysinfo["port{$port}cid"] ?? "N/A";
      $dahdiscan[$port]['name'] = $sysinfo["port{$port}name"] ?? "N/A";
      $dahdiscan[$port]['dest'] = $sysinfo["port{$port}dest"] ?? "N/A";
      $dahdiscan[$port]['signalling'] = "";
      $dahdiscan[$port]['error'] = "";
      $dahdiscan[$port]['ec'] = "";
    }

    if (file_exists("/proc/dahdi/1") && is_readable("/proc/dahdi/1")) {
      foreach (file("/proc/dahdi/1") as $l) {
        if (preg_match('/^\s+(\d)\s[^\s]+\s([^\s]+)\s(.+)$/', $l, $out)) {
          # [ portnum, signalling, details ]
          # Note that signalling is the OPPOSITE of the channel type. A FXO port 
          # has FXS signalling
          $dahdiscan[$out[1]]['signalling'] = $out[2];
          if (strpos($out[3], " RED ") !== false) {
            $dahdiscan[$out[1]]['error'] = " (Line Fault)";
          }
        }
      }
    }
    return $dahdiscan;
  }
}
