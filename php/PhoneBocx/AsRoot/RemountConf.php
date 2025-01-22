<?php

namespace PhoneBocx\AsRoot;

use PhoneBocx\CoreInfo;

class RemountConf
{
    public function __construct()
    {
        if (posix_getuid() !== 0) {
            throw new \Exception("I am not being run as root, I am being run as " . posix_getuid());
        }
    }

    public function mountRw(): array
    {
        return $this->remount("rw");
    }

    public function mountRo(): array
    {
        return $this->remount("ro");
    }

    private function remount(string $type): array
    {
        $mounts = $this->getMountpoints();
        $retarr = [];
        foreach (array_keys($mounts) as $m) {
            $cmd = "mount -o remount,$type $m";
            unset($output);
            exec($cmd, $output, $result);
            $retarr[$m] = ["cmd" => $cmd, "output" => $output, "result" => $result];
        }
        return $retarr;
    }

    public function isReadWrite(bool $throw = true): bool
    {
        $part = CoreInfo::getConfVol();
        if (!$part) {
            if ($throw) {
                throw new \Exception("Conf Vol does not exist");
            }
            return false;
        }
        $foundro = false;
        $foundrw = false;
        foreach (self::getMountpoints() as $m => $row) {
            if (strpos($row[3], "ro,") === 0) {
                $foundro = true;
            } else if (strpos($row[3], "rw,") === 0) {
                $foundrw = true;
            }
        }
        // If something has happened and only ONE is rw, that's not
        // right. We only return true if everything we've found is
        // rw, and nothing is ro.
        if ($foundro) {
            return false;
        }
        if ($foundrw) {
            return true;
        }
        // Otherwise we didn't find anything??
        throw new \Exception("Nothing found mounted");
    }

    public function getMountpoints(): array
    {
        $part = CoreInfo::getConfVol();
        if (!$part) {
            return [];
        }
        $retarr = [];
        $lines = file("/proc/mounts", \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $l) {
            $tmparr = explode(" ", $l);
            if ($tmparr[0] == $part) {
                $retarr[$tmparr[1]] = $tmparr;
            }
        }
        return $retarr;
    }
}
