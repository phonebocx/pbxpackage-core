<?php

namespace PhoneBocx\Services;

use PhoneBocx\Interfaces\ServiceInterface;
use PhoneBocx\PhoneBocx;

class ServiceMgr
{
    private PhoneBocx $pbx;
    private array $pendingjobs;
    private int $lastcheck;

    public function __construct(PhoneBocx $pbx)
    {
        $this->pbx = $pbx;
        $this->lastcheck = time();
        $this->pendingjobs = $this->getAllScheduledTasks();
    }

    public function getLastRunUtime(string $sname): int
    {
        $key = 'lastrun_' . $sname;
        $v = (int) $this->pbx->getKey($key, 0);
        return $v;
    }

    public function getLastFailUtime(string $sname): int
    {
        $key = 'lastfail_' . $sname;
        $v = (int) $this->pbx->getKey($key, 0);
        return $v;
    }

    public function setTaskFailed(Task $t)
    {
        $key = 'lastfail_' . $t->getJobKey();
        $this->pbx->setKey($key, time());
    }

    public function setTaskCompleted(Task $t)
    {
        $key = 'lastrun_' . $t->getJobKey();
        $this->pbx->setKey($key, time());
    }

    public function getAllScheduledTasks()
    {
        $s = $this->pbx->getHookObj()->findSchedulerFuncs();
        $retarr = [];
        foreach ($s as $k => $conf) {
            $class = $conf['sclass'];
            if (!class_exists($class)) {
                print "$k asked for class $class which does not exist\n";
                continue;
            }
            $method = $conf['gnrtoverride'] ?? 'getNextRunTime';
            if (!method_exists($class, $method)) {
                print "$s asked for method $method in class $class which does not exist\n";
                continue;
            }
            $t = new Task($this->pbx, $k, $class, $method);
            $lastutime = $this->getLastRunUtime($k);
            $t->setLastUtime($lastutime);
            $next = $t->getNextRunUtime();
            // If we should run, this will be greater than zero
            if ($next > 0) {
                $lastfail = $this->getLastFailUtime($k);
                // If it failed within the last 30 seconds, add some fuzz
                $cutoff = time() - 30;
                if ($lastfail > $cutoff) {
                    $fuzz = mt_rand(15, 30);
                    print "Failed - $lastfail greater than $cutoff, adding $fuzz - was $next,";
                    $t->addFuzz($fuzz);
                    $next = $t->getNextRunUtime();
                    print "is now $next\n";
                }
                $retarr[$next][] = $t;
            }
        }
        return $retarr;
    }

    public function getNextTask(?array $tasklist = null): ?Task
    {
        if ($tasklist === null) {
            $tasklist = $this->getAllScheduledTasks();
        }
        if (!$tasklist) {
            return null;
        }
        $nexttask = min(array_keys($tasklist));
        $task = array_shift($tasklist[$nexttask]);
        return $task;
    }

    public function getTaskLogs()
    {
        $logjson = $this->pbx->getKey('logarr');
        if (!$logjson) {
            $logjson = '[]';
        }
        $logarr = json_decode($logjson, true);
        while (count($logarr) > 10) {
            array_shift($logarr);
        }
        return $logarr;
    }

    public function saveTaskLog(array $ret): array
    {
        $logs = $this->getTaskLogs();
        $i = time();
        $logs[$i] = $ret;
        $this->pbx->setKey('logarr', json_encode($logs));
        return $ret;
    }

    public function waitForTask(Task $t): Task
    {
        if ($t->canRun()) {
            return $t;
        }
        $sleeptime = $t->getSleepTime();
        if ($sleeptime > 10) {
            $t->addLog("Sleeptime $sleeptime is > 10, only sleeping for 10");
            sleep(10);
            return $t;
        }
        $t->addLog("Sleeptime $sleeptime, but only sleeping for 4");
        sleep(4);
        return $t;
    }

    public function runTask(Task $t)
    {
        if (!$t->canRun()) {
            throw new \Exception("I was given a task that is not ready to run");
        }
        $retarr = ["RunTask Triggered"];
        $retarr[] = "Debug  " . json_encode($t->getDebugArray());
        $class = $t->getServiceClass();
        if (!$class->launch($retarr)) {
            $retarr[] = "launch() failed!!!";
            $this->setTaskFailed($t);
        } else {
            $this->setTaskCompleted($t);
        }
        foreach ($t->getLogs() as $r) {
            $retarr[] = "Joblog: $r";
        }
        return $this->saveTaskLog($retarr);
    }
}
