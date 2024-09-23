<?php

namespace PhoneBocx;

use PhoneBocx\Queue\Pdo\SqlitePdoQueue;

class Queue
{
    private static $pdo;
    private static $cache = [];

    public static function getSqliteFile()
    {
        return "/spool/data/jobqueue.sq3";
    }

    public static function getPdo($regen = false): \PDO
    {
        $dbfile = self::getSqliteFile();

        if (!is_dir(dirname($dbfile))) {
            throw new \Exception("$dbfile dir missing, queue unavailable");
        }
        if ($regen || !self::$pdo) {
            if (!file_exists($dbfile)) {
                touch($dbfile);
                chmod($dbfile, 0777);
            }
            self::$pdo = new \PDO("sqlite:" . $dbfile, '', '', [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            self::$pdo->exec('PRAGMA journal_mode=WAL');
        }
        return self::$pdo;
    }

    public static function getQueue($name = "core", $regen = false): SqlitePdoQueue
    {
        if ($regen || empty(self::$cache[$name])) {
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
            self::$cache[$name] = new SqlitePdoQueue($pdo, $name);
        }
        return self::$cache[$name];
    }

    public static function genTable($pdo, $name)
    {
        $src = __DIR__ . "/Queue/res/sqlite/10-table.sql";
        if (!file_exists($src)) {
            throw new \Exception("Can't find $src");
        }
        foreach (file($src) as $sql) {
            $query = trim(str_replace('{{table_name}}', $name, $sql));
            $pdo->query($query);
        }
    }
}
