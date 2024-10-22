<?php

namespace PhoneBocx;

class LedController
{
    private string $filename = "i2cbus.json";
    private string $fullpath;
    private ?int $bus = null;

    private array $runledvalues = [
        "off" => 0,
        "flash" => 1,
        "slow" => 1,
        "on" => 2,
        "longon" => 2,
        "fast" => 3,
        "med" => 4
    ];

    private array $ua32values = [
        "off" => 0,
        "on" => 1,
        "fast" => 2,
        "slow" => 3,
        "flash" => 3,
        "brief" => 4,
    ];

    public function __construct(bool $recheck = false)
    {
        $this->fullpath = $this->getFullPath();
        if ($recheck) {
            @unlink($this->fullpath);
        }
        if (!file_exists($this->fullpath)) {
            $this->bus = $this->findI2CBus();
            $this->updateBusFile();
        } else {
            $this->bus = $this->loadBusFile();
        }
    }

    public function loadBusFile(): ?int
    {
        return json_decode(file_get_contents($this->fullpath));
    }

    public function updateBusFile()
    {
        file_put_contents($this->fullpath, json_encode($this->bus));
    }

    public function getFullPath(): string
    {
        return PhoneBocx::create()->getBaseDir() . "/" . $this->filename;
    }

    public function findI2CBus(): ?int
    {
        if (file_exists($this->fullpath)) {
            return json_decode(file_get_contents($this->fullpath));
        }
        $cmd = "/usr/sbin/i2cdetect";
        if (!file_exists($cmd)) {
            return null;
        }
        $output = [];
        $res = exec("$cmd -l", $output, $result);
        if ($result !== 0) {
            throw new \Exception("Error when running $cmd - $result and $res");
        }
        foreach ($output as $row) {
            if (preg_match('/i2c-(\d).+Synopsys/', $row, $out)) {
                $val = $this->getRunLedValue($out[1]);
                if ($val !== null) {
                    return $out[1];
                }
            }
        }
        // It never responded
        return null;
    }

    public function getRunLedValue(?int $busnum = null): ?int
    {
        $cmd = "/usr/sbin/i2cget";
        if (!file_exists($cmd)) {
            return null;
        }
        if ($busnum === null) {
            $busnum = $this->bus;
        }
        if ($busnum === null) {
            // It's really not there.
            return null;
        }
        $output = [];
        $fullcmd = "$cmd -y $busnum 0x55 41 2>/dev/null";
        $s = exec($fullcmd, $output, $ret);
        if ($ret != 0) {
            // print "Failed with $fullcmd\n";
            return null;
        }
        if (empty($output[0])) {
            throw new \Exception("Something's broken with $fullcmd, no output[0]");
        }
        return intval($output[0], 16);
    }

    public function setRunLed(string $mode): ?int
    {
        if ($this->bus === null) {
            return null;
        }

        $val = $this->runledvalues[$mode] ?? 9;
        $cmd = "/usr/sbin/i2cset -y " . $this->bus . " 0x55 41 $val 2>/dev/null";
        $output = [];
        $s = exec($cmd, $output, $ret);
        if ($ret !== 0) {
            return null;
        }
        return $val;
    }

    public function toggleRunLed(): ?int
    {
        if ($this->bus === null) {
            return null;
        }
        $current = $this->getRunLedValue();
        if ($current === 0) {
            // It's off. Turn it on
            return $this->setRunLed('on');
        } else {
            return $this->setRunLed('off');
        }
    }

    public function setPortLed(int $portnum, string $mode): ?int
    {
        $lednum = $portnum - 1;
        $cfgfile = "/proc/ua32xx/ledcfg/$lednum";
        if (!file_exists($cfgfile)) {
            return null;
        }
        $val = $this->ua32values[$mode] ?? 0;
        file_put_contents($cfgfile, $val);
        return $val;
    }
}
