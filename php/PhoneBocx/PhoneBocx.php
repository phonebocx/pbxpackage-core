<?php

namespace PhoneBocx;

use GuzzleHttp\Client;

/** @package PhoneBocx */
class PhoneBocx
{
    private static $me = false;
    private $dbfile;
    private $dbh;

    /** @return PhoneBocx */
    public static function create()
    {
        if (!self::$me) {
            self::$me = new self;
        }
        return self::$me;
    }

    public static function getProdDbFilename()
    {
        return FileLocations::getProdDbFilename();
    }

    public static function checkDbStructure()
    {
        $pbx = self::create();
        $pdo = $pbx->getPdo();
        try {
            $q = $pdo->query('select * from settings limit 1');
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), "no such table") !== false) {
                $pdo->query('CREATE TABLE IF NOT EXISTS settings ( k TEXT PRIMARY KEY, v TEXT )');
                return true;
            } else {
                throw $e;
            }
        }
        return true;
    }

    // Declare this private so it can't be instantiated accidentally
    private function __construct()
    {
        foreach (FileLocations::getDbFiles() as $d => $f) {
            // /spool will only be a directory if it is mounted
            if (is_dir($d)) {
                $dirname = dirname($f);
                if (!is_dir($dirname)) {
                    mkdir($dirname, 0777, true);
                    @chown($d, "asterisk");
                    @chgrp($d, "asterisk");
                    chmod($d, 0777);
                }
                $created = false;
                if (!file_exists($f)) {
                    $created = true;
                    touch($f);
                    chmod($f, 0777);
                }
                $this->dbfile = $f;
                if ($created) {
                    $dbh = $this->getPdo();
                    $dbh->query('CREATE TABLE IF NOT EXISTS settings ( k TEXT PRIMARY KEY, v TEXT )');
                }
                return true;
            }
        }
        // We somehow didn't manage to find somewhere to put our sqlite file??
        throw new \Exception("Couldn't find where to put sqlite file");
    }

    public static function safeGet($dest, $url, $throw = true)
    {
        $destdir = dirname($dest);
        if (!is_dir($destdir)) {
            throw new \Exception("$destdir is not a directory");
        }
        if (!is_writable($destdir)) {
            throw new \Exception("$destdir is not writable");
        }
        $client = new Client(["allow_redirects" => true]);
        $f = tempnam(dirname($dest), basename($dest));
        $params = ["sink" => $f];
        try {
            $client->request('GET', $url, $params);
        } catch (\Exception $e) {
            unlink($f);
            if ($throw) {
                throw $e;
            } // else
            return false;
        }
        rename($f, $dest);
        chmod($dest, 0777);
        return true;
    }

    public function getPdo()
    {
        if ($this->dbh) {
            return $this->dbh;
        }

        if (!file_exists($this->dbfile)) {
            throw new \Exception("Can't getPdo on " . $this->dbfile . " it doesn't exist!");
        }
        $this->dbh = new \PDO("sqlite:" . $this->dbfile, '', '', [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $this->dbh->exec('PRAGMA journal_mode=WAL');
        return $this->dbh;
    }

    public function getDbFilename()
    {
        return $this->dbfile;
    }

    public function getSettings()
    {
        $dbh = $this->getPdo();
        try {
            $q = $dbh->prepare('select * from settings');
            $q->execute();
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), "no such table") !== false) {
                $dbh->query('CREATE TABLE IF NOT EXISTS settings ( k TEXT PRIMARY KEY, v TEXT )');
                return [];
            } else {
                throw $e;
            }
        }
        return $q->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function getKey($k, $default = null)
    {
        $dbh = $this->getPdo();
        try {
            $q = $dbh->prepare('select v from settings where k=?');
            $q->execute([$k]);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), "no such table") !== false) {
                $dbh->query('CREATE TABLE IF NOT EXISTS settings ( k TEXT PRIMARY KEY, v TEXT )');
                return $default;
            } else {
                throw $e;
            }
        }
        $row = $q->fetch(\PDO::FETCH_COLUMN);
        if ($row) {
            return $row;
        }
        return $default;
    }

    // Let this one crash.
    public function setKey($k, $v = false)
    {
        $dbh = $this->getPdo();
        if ($v === false) {
            $q = $dbh->prepare('delete from settings where k=?');
            $q->execute([$k]);
            return $k;
        }
        $q = $dbh->prepare('replace into settings (k, v) values (?, ?)');
        $q->execute([$k, $v]);
        return $k;
    }

    public function getRunningOs()
    {
        $cmdline = file_get_contents("/proc/cmdline");
        if (!preg_match(":BOOT_IMAGE=/boot/([^/]+):", $cmdline, $out)) {
            return "live";
        }
        return $out[1];
    }

    public function getDevUuid()
    {
        if (!is_readable("/sys/class/dmi/id/product_uuid")) {
            return "unreadable";
        }
        $uuid = trim(file_get_contents("/sys/class/dmi/id/product_uuid"));
        if (strlen($uuid) != 36) {
            return "invalid-len-" . strlen($uuid);
        }
        return $uuid;
    }
}
