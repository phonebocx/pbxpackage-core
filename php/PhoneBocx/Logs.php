<?php

namespace PhoneBocx;

/** @package PhoneBocx */
class Logs
{
    public static function addLogEntry(string $message, string $type = 'Log', array $metadata = [], bool $throw = false)
    {
        $dbh = PhoneBocx::create()->getPdo();
        try {
            $q = $dbh->prepare('insert into logs(type,message,metadata) values(?,?,?)');
            $res = $q->execute([$type, $message, json_encode($metadata)]);
        } catch (\Exception $e) {
            if ($throw) {
                throw new \Exception("Second error when adding log entry", 0, $e);
            }
            if (strpos($e->getMessage(), "no such table") !== false) {
                self::createLogDb($dbh);
                return self::addLogEntry($message, $type, $metadata, true);
            } else {
                throw $e;
            }
        }
        return $res;
    }
    public static function getHumanLogs(int $limit = 20, string $splitaftertime = ": ", int $maxmsglen = 80): array
    {
        $retarr = [];
        $logs = self::getLogs($limit);
        foreach ($logs as $row) {
            $retarr[] = $row['t'] . $splitaftertime . "(" . $row['type'] . ") " . substr($row['message'], 0, $maxmsglen);
        }
        return $retarr;
    }

    public static function getLogs(int $limit = 20): array
    {
        $dbh = PhoneBocx::create()->getPdo();
        try {
            $q = $dbh->prepare('select * from logs order by t desc limit ?');
            $q->execute([$limit]);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), "no such table") !== false) {
                self::createLogDb($dbh);
                return [];
            } else {
                throw $e;
            }
        }
        return $q->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function pruneLogs(\PDO $dbh)
    {
        $c = $dbh->query('select count(1) from logs');
        $res = $c->fetchAll(\PDO::FETCH_ASSOC);
        var_dump($res);
    }

    public static function createLogDb(\PDO $dbh)
    {
        $q = [
            'drop table if exists logs',
            'create table logs (t timestamp default current_timestamp, type char(32), message char(128), metadata blob)',
            'create index lindex on logs(t)'
        ];
        foreach ($q as $sql) {
            print "Running $sql\n";
            $dbh->query($sql);
        }
    }
}
