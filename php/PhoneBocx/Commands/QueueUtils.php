<?php

namespace PhoneBocx\Commands;

use PhoneBocx\Queue;

/**
 * Basic Queue stats command class, as a shim
 */
class QueueUtils
{
    private static ?Queue $q = null;

    private static function getQueueObj()
    {
        $name = "core";
        if (!self::$q) {
            self::$q = Queue::create($name);
        }
        return self::$q;
    }

    public static function getSummary()
    {
        $q = self::getQueueObj();
        $pdo = $q->getPdo();
        $sql = 'SELECT id, item, serialized FROM ' . $q->getTablename();
        $p = $pdo->prepare($sql);
        return json_encode($p->fetchAll(\PDO::FETCH_ASSOC));
    }
}
