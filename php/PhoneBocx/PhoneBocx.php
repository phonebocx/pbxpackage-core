<?php

namespace PhoneBocx;

use GuzzleHttp\Client;
use PhoneBocx\Models\HookModel;

/** @package PhoneBocx */
class PhoneBocx
{
    private static $me = false;
    private static ?string $basedir = null;

    private $dbfile;
    private $dbh;
    private array $phphooks;
    private Hooks $h;

    /** @return PhoneBocx */
    public static function create()
    {
        if (!self::$me) {
            self::$me = new self;
        }
        return self::$me;
    }

    public static function getBaseDir($default = '/var/run/distro'): string
    {
        if (self::$basedir === null) {
            self::$basedir = getenv('BASEDIR');
            if (!self::$basedir) {
                self::$basedir = $default;
            }
            // If it doesn't exist, create it!
            if (!is_dir(self::$basedir)) {
                mkdir(self::$basedir);
                chmod(self::$basedir, 0777);
            }
        }
        return self::$basedir;
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

    public static function boot(array $autoloaders = []): PhoneBocx
    {
        $pb = PhoneBocx::create();
        if (!empty($autoloaders)) {
            $hook = $pb->getHookObj();
            foreach ($autoloaders as $m) {
                $hook->processAutoloader($m);
            }
        }
        $pb->triggerHook('boot');
        return $pb;
    }

    // Declare this private so it can't be instantiated accidentally
    private function __construct()
    {
        $dbfound = false;
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
                $dbfound = true;
                break;
            }
        }
        if (!$dbfound) {
            // We somehow didn't manage to find somewhere to put our sqlite file??
            throw new \Exception("Couldn't find where to put sqlite file");
        }
        $this->refreshHooks();
        return $this;
    }

    /**
     * Refresh the hook object. Can be used to block a hook
     * being run, or modify it or whatever.
     *
     * @return Hooks
     */
    public function refreshHooks(): Hooks
    {
        $this->phphooks = [];
        foreach (Packages::getLocalPackages() as $p => $pdir) {
            $hookfile = "$pdir/meta/hooks/phphooks.php";
            if (file_exists($hookfile)) {
                $hooksettings = include($hookfile);
                $this->phphooks[$p] = ["dir" => $pdir, "hookfile" => $hookfile, "hooks" => $hooksettings];
            }
        }
        $this->h = new Hooks($this, $this->phphooks);
        return $this->h;
    }

    public function getHookObj(): Hooks
    {
        return $this->h;
    }

    public function triggerHook(string $hookname, array $params = []): array
    {
        $model = new HookModel($params);
        // Note that the HookModel is returned in __model if you can't pass
        // your params by ref.
        return $this->h->trigger($hookname, $model);
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

    public function getPdo(): \PDO
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
