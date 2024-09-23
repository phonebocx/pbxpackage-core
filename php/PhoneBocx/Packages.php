<?php

namespace PhoneBocx;

class Packages
{
    private static $basedir = "/var/run/phonebocx";
    private static $pkgurl = "/packages.php";
    public static $update = false;
    public static $short = false;

    public static $quiet = true;

    public static function getRemotePackages()
    {
        $json = json_decode(self::getCurrentJson(), true);
        return array_keys($json);
    }

    public static function getLocalPackages()
    {
        $retarr = [];
        $glob = glob("/pbx/*/meta");
        foreach ($glob as $m) {
            $pkgname = basename(dirname($m));
            $retarr[] = $pkgname;
        }
        return $retarr;
    }

    public static function getCurrentJson()
    {
        $refresh = time() - 300;
        if (!is_dir(self::$basedir)) {
            mkdir(self::$basedir, 0777, true);
            chmod(self::$basedir, 0777);
        }
        $filename = self::$basedir . "/current.json";
        $update = self::$update;
        if (!file_exists($filename)) {
            $update = true;
        } else {
            $s = stat($filename);
            if ($s['mtime'] < $refresh) {
                $update = true;
            }
        }
        if ($update) {
            $update = self::updateFromApi($filename);
            if ($update !== true) {
                throw new \Exception("Update failed");
            }
        }
        if (!file_exists($filename)) {
            throw new \Exception("$filename does not exist");
        }
        return file_get_contents($filename);
    }

    public static function updateFromApi($dest = false)
    {
        if (!$dest) {
            $dest = self::$basedir . "/current.json";
        }
        $build = PhoneBocx::create()->getKey('os_build', 'unknown');
        if (!file_exists("/tmp/packages.lock")) {
            touch("/tmp/packages.lock");
            chmod("/tmp/packages.lock", 0777);
        }
        $lockfh = fopen("/tmp/packages.lock", "w");
        $attempts = 5;
        $locked = false;
        while ($attempts--) {
            if (flock($lockfh, LOCK_EX | LOCK_NB)) {
                // We locked, break out of the while
                $locked = true;
                break;
            }
            // Can't lock. Sleep a second and try again;
            sleep(1);
        }
        if (!$locked) {
            print "Couldn't lock after 5 attempts, giving up\n";
            return false;
        }
        try {
            $url = FileLocations::getBaseUrl() . self::$pkgurl . "?os_build=$build";
            PhoneBocx::safeGet($dest, $url, true);
        } catch (\Exception $e) {
            fclose($lockfh);
            if (!self::$quiet) {
                print "updateFromApi Error: " . $e->getMessage() . "\n";
                return $e->getMessage();
            }
            return false;
        }
        fclose($lockfh);
        return true;
    }

    public static function remotePkgInfo($pkgname, $asarray = false)
    {
        $json = self::getCurrentJson();
        if (!$json) {
            return false;
        }
        $remote = json_decode($json, true);
        $p = $remote[$pkgname] ?? ["commit" => "00000000", "utime" => "0", "modified" => false];
        if ($p['modified']) {
            $modified = "true";
        } else {
            $modified = "false";
        }
        if (self::$short) {
            $p['commit'] = substr($p['commit'], 0, 8);
        }
        $array = [$p['commit'], $p['utime'], $modified];
        if ($asarray) {
            return $array;
        }
        return join("-", $array);
    }

    public static function localPkgInfo($pkgname, $asarray = false)
    {
        $infofile = "/pbx/$pkgname/meta/pkginfo.json";
        if (!file_exists($infofile)) {
            return "00000000-0-true";
        }
        $p = json_decode(file_get_contents($infofile), true);
        if ($p['modified']) {
            $modified = "true";
        } else {
            $modified = "false";
        }
        if (self::$short) {
            $p['commit'] = substr($p['commit'], 0, 8);
        }
        $array = [$p['commit'], $p['utime'], $modified];
        if ($asarray) {
            return $array;
        }
        return join("-", $array);
    }

    public static function doesPkgNeedUpdate($pkgname)
    {
        $localver = self::localPkgInfo($pkgname);
        // If this is unpackaged, no.
        if ($localver == "00000000-0-true") {
            return false;
        }
        $remotever = self::remotePkgInfo($pkgname);

        // If local matches remote, no.
        if ($remotever == $localver) {
            return false;
        }

        // If it doesn't exist remotely, no.
        if ($remotever == "00000000-0-true") {
            return false;
        };

        // ACTUAL COMPARISON CODE:
        // If the utime of the remote package is higher than
        // the local package, yes. It needs an update.
        $localarr = explode("-", $localver);
        $remotearr = explode("-", $remotever);
        if ($localarr[1] < $remotearr[1]) {
            return true;
        }
        return false;
    }

    public static function getPkgVer($pkginfo, $showutime = false)
    {
        if (!is_array($pkginfo)) {
            $pkginfo = explode('-', $pkginfo);
        }
        $commit = substr($pkginfo[0], 0, 8);
        $utime = $pkginfo[1];
        if ($pkginfo[2] == "true") {
            if ($showutime) {
                return "mod-$utime-$commit";
            }
            return "mod-$commit";
        }
        if ($showutime) {
            return "git-$utime-$commit";
        }
        return "git-$commit";
    }

    public static function getPkgDisplay()
    {
        $pkginfo = [];
        try {
            foreach (self::getRemotePackages() as $p) {
                $pkginfo[$p] = ["remote" => self::remotePkgInfo($p, true), "local" => false];
            }
        } catch (\Exception $e) {
            if (!self::$quiet) {
                throw $e;
            }
            return "";
        }
        foreach (self::getLocalPackages() as $p) {
            if (empty($pkginfo[$p])) {
                $pkginfo[$p] = ["remote" => false];
            }
            $pkginfo[$p]['local'] = self::localPkgInfo($p, true);
        }
        $pieces = [];
        foreach ($pkginfo as $p => $data) {
            $pieces[$p] = self::formatPkgInfo($p, $data);
        }
        $chunklen = 35;
        $maxlen = 36;
        $lines = [];
        $thisline = "";
        foreach ($pieces as $name => $desc) {
            $thisline .= substr(str_pad(sprintf("%10s: %s", $name, $desc), $chunklen), 0, $chunklen);
            if (strlen($thisline) > $maxlen) {
                $lines[] = $thisline;
                $thisline = "";
            }
        }
        if ($thisline) {
            $lines[] = $thisline;
        }

        return join("\n", $lines) . "\n";
    }

    public static function formatPkgInfo($name, $i)
    {
        if (!$i['local']) {
            return self::getPkgVer($i['remote']) . " (New)";
        }
        if (!$i['remote']) {
            return self::getPkgVer($i['local']) . " (Unknown)";
        }
        if (self::doesPkgNeedUpdate($name)) {
            return self::getPkgVer($i['remote']) . " (Update)";
        }
        return self::getPkgVer($i['remote']);
    }
}
