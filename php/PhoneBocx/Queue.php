<?php

namespace PhoneBocx;

use PhoneBocx\Models\QueueJobInterface;
use PhoneBocx\Queue\NoItemAvailableException;
use PhoneBocx\Queue\Pdo\SqlitePdoQueue;
use PhoneBocx\Queue\QueueConstructor;

class Queue extends SqlitePdoQueue
{
    private static array $queuecache = [];

    public static function create(string $name = 'core'): Queue
    {
        if (empty(self::$queuecache[$name])) {
            $pdo = QueueConstructor::checkQueueDb($name);
            self::$queuecache[$name] = new Queue($pdo, $name);
        }
        return self::$queuecache[$name];
    }

    public function spoolJob($j, int $runafter = 0)
    {
        // Allow runafter to be overridden
        if ($runafter === 0) {
            $runafter = $j->runAfter();
        }
        if ($runafter < time()) {
            $runafter = time() + 5;
        }
        // We make this an array so we can make sure the autoloader for
        // the job has been run when it's run. Otherwise things break,
        // as you can't unserialize something that doesn't exist
        $this->push(["module" => $j->getPackage(), "job" => $j], $runafter);
    }

    public function getNextJob(string $byref = ""): ?QueueJobInterface
    {
        try {
            if ($byref) {
                $z = $this->popByRef($byref);
            } else {
                $z = $this->pop();
            }
            // We should have an array.
            if (!is_array($z)) {
                return null;
            }
            // If this module hasn't been autoloaded, load it
            PhoneBocx::create()->autoload([$z['module']]);
            return $z['job'];
        } catch (NoItemAvailableException $e) {
            return null;
        }
    }

    public function runNextJob(string $byref = ""): ?QueueJobInterface
    {
        $j = $this->getNextJob($byref);
        if (!$j) {
            return null;
        }

        // Make sure we can run
        $current = $j->getCurrentAttempts();
        if ($current > $j->maxAttempts()) {
            $j->onFatal("Too many attempts - $current > " . $j->maxAttempts());
            return null;
        }

        try {
            $j->incrementAttempts();
            $res = $j->runJob();
        } catch (\Exception $e) {
            $j->onFailure("Exception " . $e->getMessage());
            return $this->resubmitFailedJob($j);
        }
        if (!$res) {
            $j->onFailure('Returned false');
            return $this->resubmitFailedJob($j);
        }
    }

    public function resubmitFailedJob(QueueJobInterface $j): QueueJobInterface
    {
        // Current should aways be positive, as we incremented it
        // before trying to run it.
        $backoff = $j->getCurrentAttempts() * $j->getFailureBackoff();
        $utime = time() + $backoff;
        print "Resubmitting job in $backoff seconds which is $utime\n";
        $this->spoolJob($j, $utime);
        return $j;
    }
}
