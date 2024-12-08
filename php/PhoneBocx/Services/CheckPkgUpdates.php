<?php

namespace PhoneBocx\Services;

class CheckPkgUpdates extends ServiceAbstraction implements ServiceInterface
{
    /**
     * Run this every 15 mins or so
     *
     * @param integer $lastutime
     * @return integer
     */
    public static function getNextRunTime(int $lastutime = 0): int
    {
        // If the last time it was run was more than 15 mins ago, run it in 10
        // seconds from now, because something ELSE may want to run first
        $cutoff = time() - 900;
        if ($lastutime < $cutoff) {
            return time() + 10;
        }
        $fuzz = mt_rand(-15, 15);
        $next = $lastutime + 900 + $fuzz;
        return $next;
    }

    public function launch(array &$retarr): bool
    {
        $retarr[] = "PkgUpdates Launched";
        return true;
    }
}
