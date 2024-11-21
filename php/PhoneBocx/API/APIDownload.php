<?php

namespace PhoneBocx\API;

use GuzzleHttp\Client;
use PhoneBocx\Logs;

class APIDownload
{
  public static function handle($a)
  {
    if (posix_getuid() !== 0) {
      Logs::addLogEntry('Not running as root - am ' . posix_getuid());
    }
    $contents = $a['contents'];
    foreach ($contents as $row) {
      $fn = $row['filename'];
      $b = basename($fn);
      if (preg_match('/wg..conf$/', $b)) {
        // Override wireguard
        $fn = self::getWireguardLoc($b);
      }
      $loc = $row['loc'];
      $path = self::getActualPath($loc, $fn);
      if (!$path) {
        Logs::addLogEntry("Unable to determine where $loc and $fn is meant to go", "System");
        continue;
      }
      if (!empty($row['contents'])) {
        $body = $row['contents'];
      } else {
        if (empty($row['url'])) {
          Logs::addLogEntry("Don't know how to handle this row, no contents or url", "System", $row);
          continue;
        }
        $c = new Client();
        $r = $c->get($row['url']);
        $body = (string) $r->getBody();
      }
      $basedir = dirname($path);
      if (!is_dir($basedir)) {
        mkdir($basedir, 0777, true);
      }
      $bytes = file_put_contents($path, $body);
      Logs::addLogEntry("Saved $bytes bytes at $path", "System");
    }
  }

  public static function getActualPath(string $loc, string $fn)
  {
    switch ($loc) {
      case 'boot':
        $cmd = "mount -o remount,rw /run/live/medium";
        exec($cmd, $out, $ret);
        return "/run/live/medium/$fn";
      case 'root':
        return $fn;
      case 'efi':
        $efi = self::mountEfi();
        if ($efi) {
          return "$efi/$fn";
        }
    }
    return null;
  }

  private static function isEfiMounted()
  {
    if (!file_exists("/dev/disk/by-label/EFI")) {
      return false;
    }
    $vol = realpath("/dev/disk/by-label/EFI");
    $mounts = file("/proc/mounts");
    foreach ($mounts as $l) {
      $line = explode(" ", $l);
      if ($line[0] == $vol) {
        return true;
      }
    }
    return false;
  }

  private static function mountEfi()
  {
    if (!file_exists("/dev/disk/by-label/EFI")) {
      return null;
    }
    if (!self::isEfiMounted()) {
      $vol = realpath("/dev/disk/by-label/EFI");
      if (!is_dir("/efi")) {
        mkdir("/efi");
      }
      $cmd = "mount -o rw $vol /efi";
      exec($cmd, $out, $ret);
    }
    if (self::isEfiMounted()) {
      return "/efi";
    }
    return null;
  }

  private static function getWireguardLoc(string $filename)
  {
    return "/var/run/wireguard/$filename";
  }
}
