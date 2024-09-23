<?php

namespace PhoneBocx\API;

use PhoneBocx\CoreInfo;
use PhoneBocx\Packages;

class Sysinfo extends Base
{
    public function getResponse(): array
    {
        $runningdist = CoreInfo::getRunningDist();
        $latestdist = CoreInfo::getLatestDist();
        $retarr = [
            "runningdist" => $runningdist,
            "latestdist" => $latestdist['meta']['general'] ?? [],
            "osupdateavail" => $this->isOSUpgradeAvailable($runningdist, $latestdist),
            "uptime" => CoreInfo::getUptime(true),
            "queuecount" => CoreInfo::getQueueCount(),
            "systemid" => CoreInfo::getSysId(),
            "serialno" => CoreInfo::getSerialNo(),
            "networkints" => CoreInfo::getInterfaceInfo(),
            "systemname" => CoreInfo::getSysName(),
            "kernel" => CoreInfo::getKernelVersion(),
            "services" => CoreInfo::getServices(),
            "packages" => Packages::getPackageReport(),
        ];
        return $retarr;
    }

    public function isOSUpgradeAvailable($running, $latest)
    {
        if (empty($latest['meta'])) {
            return false;
        }
        if ($latest['meta']['general']['utime'] > $running['utime']) {
            return $latest['meta']['general'];
        }
        return false;
    }
}
