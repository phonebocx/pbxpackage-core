<?php

namespace PhoneBocx\Services;

use PhoneBocx\PhoneBocx;

class JobSystemdService
{
    private PhoneBocx $pbx;
    private bool $debug = false;

    public static function launch(string $param = "")
    {
        $jobrunner = new JobSystemdService($param);
        $jobrunner->go();
    }

    public function __construct(string $param = "")
    {
        $this->pbx = PhoneBocx::create();
        if ($param == "debug") {
            $this->debug = true;
        }
    }

    public function getTimeStr()
    {
        return microtime(true) . ": ";
    }

    public function stdout(string $msg)
    {
        if ($this->debug) {
            $fh = fopen("php://stdout", "w+");
            fprintf($fh, $this->getTimeStr() . "JobService DEBUG: %s\n", $msg);
            fclose($fh);
        }
    }

    public function stderr(string $msg)
    {
        $fh = fopen("php://stderr", "w+");
        fprintf($fh, $this->getTimeStr() . "JobService: %s\n", $msg);
        fclose($fh);
    }

    public function go()
    {
        $loops = 50;
        $this->stderr("Service Starting, will do $loops loops before restarting");
        $mgr = $this->pbx->getServiceMgr();
        while ($loops-- > 1) {
            $this->stderr("Starting loop, $loops remaining");
            $alltasks = $mgr->getAllScheduledTasks();
            if (!$alltasks) {
                $this->stdout("Nothing in alltasks found, sleeping for 5 seconds and trying again");
                sleep(5);
                continue;
            }
            // Check that we have a next task
            $task = $mgr->getNextTask($alltasks);
            if (!$task) {
                $this->stdout("Nothing returned by getNextTask, sleeping for 5 seconds and trying again");
                sleep(5);
                continue;
            }
            // Now we wait for the task to be ready. This is broken up into
            // multiple waits because we may need to come back to this and
            // re-check getAllScheduledTasks in the middle. But at the moment,
            // that getAll is only run once per loop.

            // Sanity check - if somehow we have to wait more than 10 times,
            // crash and burn.
            $sanity = 10;
            $this->stdout("Waiting for " . $task->getJobDescription());
            while (!$task->canRun()) {
                if ($sanity--) {
                    $task = $mgr->waitForTask($task);
                } else {
                    $this->stderr("Exiting - sanity check had me wait more than 10 times for " . json_encode($task->getDebugArray()));
                    exit;
                }
            }
            $this->stdout("Success, can run " . $task->getJobDescription());
            $res = $mgr->runTask($task);
            $this->stdout("Debug: " . json_encode($res));
        }
    }
}