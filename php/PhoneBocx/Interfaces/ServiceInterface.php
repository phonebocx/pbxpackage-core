<?php

namespace PhoneBocx\Interfaces;

use PhoneBocx\PhoneBocx;

interface ServiceInterface
{
    /**
     * When should this service run next. If it returns zero,
     * do not schedule the task, and ask again later.
     *
     * @param integer $lastutime
     * @return integer
     */
    public static function getNextRunTime(int $lastutime = 0): int;

    public function __construct(PhoneBocx $pbx);

    public function isCurrentlyRunning(): bool;

    public function launch(): bool;
}
