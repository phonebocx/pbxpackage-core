<?php

namespace PhoneBocx\WebUI;

use PhoneBocx\AsRoot\ConsoleScreenshot;
use PhoneBocx\CoreInfo;
use PhoneBocx\Packages;
use PhoneBocx\PeriodText;

class MainPage extends Base
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
        ];
        $c = new ConsoleScreenshot(true);
        $retarr['consoleneedsrefresh'] = $c->needsRefresh();
        $seconds = $c->getScreenshotAge();
        if ($seconds === null) {
            $retarr['consoleage'] = "Console screenshot not available";
        } else {
            $retarr['consoleseconds'] = $seconds;
            $age = time() - $seconds;
            $retarr['consolediff'] = $age;
            if ($seconds > 3600) {
                $retarr['consoleage'] = "Last updated more than an hour ago";
            } else {
                $retarr['consoleage'] = "Last updated " . PeriodText::toStr($age) . " ago";
            }
        }
        try {
            $retarr['packages'] = Packages::getPackageReport();
        } catch (\Exception $e) {
            $retarr['packages'] = [];
            $retarr['error'] = $e->getMessage();
        }
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
