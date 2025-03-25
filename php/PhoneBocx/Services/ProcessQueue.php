<?php

namespace PhoneBocx\Services;

use PhoneBocx\Queue;

class ProcessQueue extends ServiceAbstraction implements ServiceInterface
{
    /**
     * Run this every 10 seconds. This PROBABLY is too fast.
     *
     * @param integer $lastutime
     * @return integer
     */
    public static function getNextRunTime(int $lastutime = 0): int
    {
        return max($lastutime + 10, time());
    }

    public function launch(array &$retarr): bool
    {
        $retarr[] = "Process Queue worker doing the needful";
        try {
            $q = Queue::create();
        } catch (\Exception $e) {
            $retarr[] = "Queue Worker failed: Error '" . $e->getMessage() . "'";
            return false;
        }

        $c = $q->count();
        if ($c) {
            $retarr[] = "There are $c jobs in the queue";
            // This should NEVER fail, as the Jobs themselves take care of this.
            $r = $q->runNextJob();
            if ($r === null) {
                // Nothing was ready to run
                $retarr[] = "No jobs were ready to run";
            } else {
                $retarr[] = "result of running the job is " . serialize($r);
            }
        }
        return true;
    }
}
