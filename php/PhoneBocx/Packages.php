<?php

namespace PhoneBocx;

class Packages
{
    private static $pkgurl = "/depot/packages.php";
    private static ?array $localpkgs = null;
    public static $update = false;
    public static $short = false;

    public static $quiet = true;

    public static function getRemotePackages()
    {
        $json = json_decode(self::getCurrentJson(), true);
        if (!is_array($json)) {
            return [];
        }
        return array_keys($json);
    }

    public static function getLocalPackages(): array
    {
        if (self::$localpkgs === null) {
            $retarr = [];
            // Find anything in /pbx first
            $glob = glob("/pbx/*/meta");
            foreach ($glob as $m) {
                $pkgdir = dirname($m);
                $pkgname = basename($pkgdir);
                $retarr[$pkgname] = $pkgdir;
            }
            // And then clobber it with anything in /pbxdev
            $glob = glob("/pbxdev/*/meta");
            foreach ($glob as $m) {
                $pkgdir = dirname($m);
                $pkgname = basename($pkgdir);
                $retarr[$pkgname] = $pkgdir;
            }
            self::$localpkgs = $retarr;
        }
        return self::$localpkgs;
    }

    public static function getPackageReport()
    {
        $retarr = [];
        foreach (self::getRemotePackages() as $p) {
            $retarr[$p] = [
                "update" => self::doesPkgNeedUpdate($p),
                "local" => self::getPkgVer(self::localPkgInfo($p, true)),
                "remote" => self::getPkgVer(self::remotePkgInfo($p, true)),
            ];
        }
        foreach (self::getLocalPackages() as $p => $loc) {
            if (empty($retarr[$p])) {
                $retarr[$p] = [
                    "remote" => "Not available",
                    "local" => self::getPkgVer(self::localPkgInfo($p, true)),
                    "update" => false,
                ];
            }
        }
        return $retarr;
    }

    public static function getCurrentJson(bool $refresh = false)
    {
        $refresh = time() - 300;
        $filename = PhoneBocx::getBaseDir() . "/current.json";
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
        chmod($filename, 0777);
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

    public static function localPkgInfo($pkg, $asarray = false)
    {
        $pkgdir = self::getLocalPackages()[$pkg] ?? "/invalid";
        $infofile = "$pkgdir/meta/pkginfo.json";
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

    public static function doesPkgNeedUpdate($pkgname, $localdir = "")
    {
        $localver = self::localPkgInfo($localdir);
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
        foreach (self::getLocalPackages() as $p => $pdir) {
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
            return self::getPkgVer($i['local']) . " (Unavail)";
        }
        if (self::doesPkgNeedUpdate($name)) {
            return self::getPkgVer($i['remote']) . " (Update)";
        }
        return self::getPkgVer($i['remote']);
    }
}
