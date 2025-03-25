<?php

namespace PhoneBocx\Queue;

class QueueConstructor
{
    private static array $pdocache = [];

    private static array $filenames = [
        "core" => ["live" => "/spool/data/jobqueue.sq3", "tmp" => "/var/run/jobqueue.sq3"],
    ];

    public static function getSqliteFileDetails($name = "core"): array
    {
        $qf = self::$filenames[$name] ?? false;
        if (!$qf) {
            // I guess this could be overridden somewhere, but don't.
            throw new \Exception("No queue definition for queue $name");
        }
        // See if live exists. If it exists, or if the directory it should
        /// be in exists, we're good to go.
        $live = $qf['live'];
        if (file_exists($live) || is_dir(dirname($live))) {
            return ["filename" => $live, "istemp" => false, "name" => $name, "perm" => true];
        }
        // It doesn't. So we should use tmp.
        $tdir = dirname($qf['tmp']);
        if (!is_dir($tdir)) {
            throw new \Exception("Can't use live queue, and $tdir does not exist");
        }
        // File is created with 0777 perms in getPdo
        return ["filename" => $qf['tmp'], "istemp" => true, "name" => $name, "perm" => false];
    }

    private static function getPdo(string $name): \PDO
    {
        // If we don't currently know about it, or it's temp, always recreate
        $current = self::$pdocache[$name] ?? ["pdo" => null, "istemp" => true];
        if (!$current['pdo'] || $current['istemp']) {
            $current = self::getSqliteFileDetails($name);
            $dbfile = $current['filename'];
            // This should never happen, as it's checked in getSqliteFileDetails
            if (!is_dir(dirname($dbfile))) {
                throw new \Exception("$dbfile dir missing, queue unavailable, current is '" . json_encode($current) . "'");
            }
            if (!file_exists($dbfile)) {
                touch($dbfile);
                chmod($dbfile, 0777);
            }
            $pdo = new \PDO("sqlite:" . $dbfile, '', '', [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('PRAGMA journal_mode=WAL');
            $current['pdo'] = $pdo;
            self::$pdocache[$name] = $current;
        }
        return $current['pdo'];
    }

    public static function checkQueueDb($name = "core", $regen = false): \PDO
    {
        $pdo = self::getPdo($name);
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
