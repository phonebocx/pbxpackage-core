<?php

namespace PhoneBocx\Queue;

class QueueConstructor
{
    private static ?\PDO $pdo = null;

    public static function getSqliteFile()
    {
        return "/spool/data/jobqueue.sq3";
    }

    private static function getPdo(): \PDO
    {
        if (self::$pdo === null) {
            $dbfile = self::getSqliteFile();

            if (!is_dir(dirname($dbfile))) {
                throw new \Exception("$dbfile dir missing, queue unavailable");
            }
            if (!file_exists($dbfile)) {
                touch($dbfile);
                chmod($dbfile, 0777);
            }
            self::$pdo = new \PDO("sqlite:" . $dbfile, '', '', [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            self::$pdo->exec('PRAGMA journal_mode=WAL');
        }
        return self::$pdo;
    }

    public static function checkQueueDb($name = "core", $regen = false): \PDO
    {
        $pdo = self::getPdo();
        if ($regen) {
            $pdo->query("drop table if exists `$name`");
        }
        try {
            $pdo->query("select 1 from $name");
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), "no such table") !== false) {
                self::genTable($pdo, $name);
            } else {
                throw $e;
            }
        }
        return $pdo;
    }

    public static function genTable($pdo, $name)
    {
        $src = __DIR__ . "/res/sqlite/10-table.sql";
        if (!file_exists($src)) {
            throw new \Exception("Can't find $src");
        }
        foreach (file($src) as $sql) {
            $query = trim(str_replace('{{table_name}}', $name, $sql));
            $pdo->query($query);
        }
    }
}
