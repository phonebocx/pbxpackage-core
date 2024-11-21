<?php

namespace PhoneBocx;

use PhoneBocx\Interfaces\ServiceInterface;

class ServiceMgr
{

    private PhoneBocx $pbx;

    public function __construct(PhoneBocx $pbx)
    {
        $this->pbx = $pbx;
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

    public function setTaskFailed(string $sname)
    {
        $key = 'lastfail_' . $sname;
        $this->pbx->setKey($key, time());
    }

    public function setTaskCompleted(string $sname)
    {
        $key = 'lastrun_' . $sname;
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
            $func = "$class::$method";
            $lastutime = $this->getLastRunUtime($k);
            $next = $func($lastutime);
            if ($next > 0) {
                $lastfail = $this->getLastFailUtime($k);
                // If it failed within the last 120 seconds, add some fluff
                $cutoff = time() - 60;
                if ($lastfail > $cutoff) {
                    $next = $next + mt_rand(15, 30);
                    print "Failed - $lastfail greater than $cutoff, next is now $next\n";
                }
                $retarr[$next][] = ["key" => $k, "class" => $class, "next" => $next];
            }
        }
        return $retarr;
    }

    public function getNextTask()
    {
        $all = $this->getAllScheduledTasks();
        if (!$all) {
            return [];
        }
        $nexttask = min(array_keys($all));
        $task = array_shift($all[$nexttask]);
        $retarr = ["due" => $nexttask, "task" => $task];
        return $retarr;
    }

    public function runNextTask()
    {
        $next = $this->getNextTask();
        if (!$next) {
            // Nothing to do.
            return;
        }
        $sleepfor = $next['due'] - time();
        if ($sleepfor > 0) {
            // If we would sleep for more than 30 seconds, just sleep for 30 and then
            // return, as something else may want to run.
            print "I want to sleep for $sleepfor for " . json_encode($next) . "\n";
            if ($sleepfor > 30) {
                print "Sleeping for only 30 secs\n";
                sleep(30);
                return;
            }
            print "Only sleeping for 2 for debugging\n";
            sleep(2);
        }
        $task = $next['task'];
        /** @var ServiceInterface $class */
        $class = new $task['class']($this->pbx);
        if (!$class->launch()) {
            $this->setTaskFailed($task['key']);
        } else {
            $this->setTaskCompleted($task['key']);
        }
    }
}
