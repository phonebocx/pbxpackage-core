<?php

namespace PhoneBocx\Services;

use PhoneBocx\PhoneBocx;

class Task
{
    private PhoneBocx $pbx;
    private string $key;
    private string $class;
    private string $method;
    private ?int $utime = null;
    private ?int $nextrun = null;
    private int $fuzz = 0;
    private int $lastutime = 0;
    private array $debuglogs = [];

    public bool $throw = true;

    public function __construct(PhoneBocx $pbx, string $key, string $class, string $method = 'getNextRunTime')
    {
        $this->pbx = $pbx;
        $this->key = $key;
        $this->class = $class;
        $this->method = $method;
    }

    public function getJobDescription(): string
    {
        return $this->key . " scheduled to run at " . $this->getNextRunUtime() . " (" . $this->getSleepTime() . " seconds away)";
    }

    public function getLogs(): array
    {
        return $this->debuglogs;
    }

    public function addLog(string $log)
    {
        $this->debuglogs[] = $log;
    }

    public function getDebugArray(): array
    {
        return [
            "key" => $this->getJobKey(),
            "class" => $this->class,
            "ut" => $this->getJobUtime(),
            "nextut" => $this->getNextRunUtime(),
            "fuzz" => $this->fuzz,
            "debuglogs" => $this->getLogs()
        ];
    }

    public function getJobKey(): string
    {
        return $this->key;
    }

    // If we DO know the lastutime the job was run, make sure we
    // don't use a previously calculated timestamp
    public function setLastUtime(int $lastutime)
    {
        $this->lastutime = $lastutime;
        $this->utime = null;
        $this->nextrun = null;
    }

    public function getJobUtime(): int
    {
        // If the lastutime has not been set, ALWAYS ask the service
        if ($this->lastutime === 0) {
            $this->utime = null;
        }
        if ($this->utime === null) {
            $func = $this->class . "::" . $this->method;
            try {
                $this->utime = $func($this->lastutime);
            } catch (\Exception $e) {
                if ($this->throw) {
                    throw $e;
                }
                print "Exception! " . $e->getMessage() . "\n";
                return 0;
            }
            // Now we have a lastutime, we don't need to keep asking,
            // so set it to be a day ago.
            $this->lastutime = $this->utime - 86400;
        }
        return $this->utime;
    }

    public function getNextRunUtime(): int
    {
        // If it returns zero or a negative, it should not be run
        if ($this->nextrun === null) {
            $ut = $this->getJobUtime();
            if ($ut < 1) {
                $this->nextrun = 0;
            } else {
                $this->nextrun = $ut + $this->fuzz;
            }
        }
        return $this->nextrun;
    }

    public function addFuzz(int $fuzz)
    {
        $this->fuzz = $fuzz;
        $this->nextrun = null;
    }

    public function getSleepTime(): int
    {
        $now = time();
        $nextrun = $this->getNextRunUtime();
        if ($nextrun < $now) {
            return 0;
        }
        return $nextrun - $now;
    }

    public function canRun(): bool
    {
        if ($this->getSleepTime() < 5) {
            return true;
        }
        return false;
    }

    public function getServiceClass(): ServiceInterface
    {
        /** @var ServiceInterface $class */
        $svclass = $this->class;
        $job = new $svclass($this->pbx);
        return $job;
    }
}
