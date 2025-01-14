<?php

namespace PhoneBocx;

class Packages
{
    private static $pkgurl = "/repo/packages.php";
    private static ?array $localpkgs = null;
    public static $update = false;
    public static $short = true;

    public static $quiet = true;

    public static function getFullPkgUrl(): string
    {
        $build = PhoneBocx::create()->getKey('os_build', 'unknown');
        // Override for the moment
        // $baseurl = FileLocations::getBaseUrl();
        $baseurl = "http://packages.sendfax.to/packages.php";
        return $baseurl . "?os_build=$build";
    }

    public static function getRemotePackages()
    {
        $json = json_decode(self::getCurrentJson(), true);
        if (!is_array($json)) {
            return [];
        }
        return array_keys($json);
    }

    public static function getLocalPackages(bool $skipdev = false): array
    {
        // No caching for the moment.
        if (true || self::$localpkgs === null) {
            $retarr = [];
            // Find anything in /pbx first
            $glob = glob("/pbx/*/meta");
            foreach ($glob as $m) {
                $pkgdir = dirname($m);
                $pkgname = basename($pkgdir);
                $retarr[$pkgname] = $pkgdir;
            }
            // And then clobber it with anything in /pbxdev if we haven't
            // been told to skip if
            if (!$skipdev) {
                $glob = glob("/pbxdev/*/meta");
                foreach ($glob as $m) {
                    $pkgdir = dirname($m);
                    $pkgname = basename($pkgdir);
                    $retarr[$pkgname] = $pkgdir;
                }
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

    public static function getCurrentJson(bool $forcerefresh = false): string
    {
        $refresh = time() - 30;
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
        if ($forcerefresh) {
            $update = true;
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
            $url = self::getFullPkgUrl();
            PhoneBocx::safeGet($dest, $url, true);
        } catch (\Exception $e) {
            fclose($lockfh);
            if (!self::$quiet) {
                print "updateFromApi Error: " . $e->getMessage() . "\n";
                print "Tried $url\n";
                return $e->getMessage();
            }
            return false;
        }
        fclose($lockfh);
        return true;
    }

    public static function parsePackageInfo(array $pkginfo, bool $forcestring = true)
    {
        $retarr = [
            "commit" => "00000000",
            "utime" => "0",
            "modified" => false,
            "descr" => "unknown",
            "releasename" => "master",
            "dev" => true
        ];
        foreach ($retarr as $k => $default) {
            $retarr[$k] = $pkginfo[$k] ?? $default;
        }
        if (self::$short) {
            $retarr['commit'] = substr($retarr['commit'], 0, 8);
        }
        // Make sure there's not any bad chars in the release name that
        // may confuse things
        $retarr['releasename'] = str_replace([" ", "-", "/"], "", $retarr['releasename']);
        $relname = $retarr['releasename'];
        // If we're on master, then there's no release name
        if ($relname === 'master') {
            $retarr['releasename'] = $retarr['commit'];
        } else {
            // If we're on a dev branch, AND we're modified, update
            if ($retarr['dev'] && $retarr['modified']) {
                $retarr['releasename'] = $retarr['commit'];
            }
        }
        if ($forcestring) {
            if ($retarr['modified']) {
                $retarr['modified'] = "true";
            } else {
                $retarr['modified'] = "false";
            }
        }
        return $retarr;
    }

    public static function remotePkgInfo($pkgname, $asarray = false)
    {
        $json = self::getCurrentJson();
        if (!$json) {
            return false;
        }
        $remote = json_decode($json, true);
        $pkginfo = self::parsePackageInfo($remote[$pkgname]);
        $array = [$pkginfo['releasename'], $pkginfo['utime'], $pkginfo['modified']];
        if ($asarray) {
            $array[] = $pkginfo['dev'];
            return $array;
        }
        return join("-", $array);
    }

    public static function localPkgInfo($pkg, $asarray = false, bool $skipdev = false)
    {
        $pkgdir = self::getLocalPackages($skipdev)[$pkg] ?? "/invalid";
        $infofile = "$pkgdir/meta/pkginfo.json";
        if (!file_exists($infofile)) {
            $p = [];
        } else {
            $p = json_decode(file_get_contents($infofile), true);
        }
        $pkginfo = self::parsePackageInfo($p);
        $array = [$pkginfo['releasename'], $pkginfo['utime'], $pkginfo['modified']];
        if ($asarray) {
            $array[] = $pkginfo['dev'];
            return $array;
        }
        return join("-", $array);
    }

    public static function doesPkgNeedUpdate($pkgname)
    {
        $localver = self::localPkgInfo($pkgname, false, true);
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
            // print $localarr[1] . " is less than " . $remotearr[1] . "\n";
            return true;
        }
        return false;
    }

    public static function getPkgVer($pkginfo, $showutime = false)
    {
        if (!is_array($pkginfo)) {
            $pkginfo = explode('-', $pkginfo);
        }
        $longname = substr($pkginfo[0], 0, 10);
        $shortname = substr($pkginfo[0], 0, 8);
        $dev = $pkginfo[3] ?? true;
        $utime = $pkginfo[1];
        $ret = [$longname];
        if ($showutime) {
            $ret[] = $utime;
        }
        // If it's modified...
        if ($pkginfo[2] == "true") {
            $ret[0] = $shortname;
            $ret[] = "mod";
        } else {
            if ($dev) {
                $ret[0] = $shortname;
                $ret[] = "dev";
            } else {
                if (strlen($longname) <= 8) {
                    array_unshift($ret, 'rel');
                }
            }
        }
        return join('-', $ret);
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
        $upgrades = [];
        $current = [];
        foreach ($pkginfo as $p => $data) {
            $current[$p] = self::getPkgVer($data['local']);
            $pieces[$p] = self::formatPkgInfo($p, $data);
            if (self::doesPkgNeedUpdate($p)) {
                $upgrades[$p] = self::getPkgVer($data['remote']);
            }
        }
        $chunklen = 35;
        $maxlen = 36;
        $lines = [];
        $thisline = "";
        $nextlines = [];
        foreach ($pieces as $name => $desc) {
            $thisline .= substr(str_pad(sprintf("%10s: %s", $name, $desc), $chunklen), 0, $chunklen);
            if (!empty($upgrades[$name])) {
                $nextlines[] = sprintf("%10s - New package version available: '%s'", $name, $upgrades[$name]);
            }
            if (strlen($thisline) > $maxlen) {
                $lines[] = $thisline;
                $thisline = "";
                foreach ($nextlines as $l) {
                    $lines[] = $l;
                }
                $nextlines = [];
            }
        }
        if ($thisline) {
            $lines[] = $thisline;
        }
        foreach ($nextlines as $l) {
            $lines[] = $l;
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
            return self::getPkgVer($i['local']) . " (Update)";
        }
        // Note this means that if a dev version is installed, and it
        // is just tagged as a release, the release name will be displayed.

        // This is so that we don't need to push out a bunch of
        // identical builds just to change the name.
        return self::getPkgVer($i['remote']);
    }

    public static function getPackageDownloadInfo(string $name, bool $refresh = false)
    {
        $j = json_decode(self::getCurrentJson($refresh), true);
        $p = $j[$name] ?? null;

        if ($p === null) {
            throw new \Exception("Unknown package $name");
        }
        $retarr = [];
        foreach (['squashfs', 'meta', 'sha256file', 'sha256', 'releasename'] as $f) {
            $retarr[$f] = $p[$f];
        }
        $retarr['filename'] = basename($retarr['squashfs']);
        return $retarr;
    }

    public static function downloadRemotePackage(string $name, string $destdir, bool $force = false)
    {
        $urls = self::getPackageDownloadInfo($name, $force);
        $pkgfile = $urls['filename'];
        if (!is_dir($destdir)) {
            mkdir($destdir, 0777, true);
        }
        $basedest = "$destdir/new.$pkgfile";
        $src = $urls['squashfs'];
        $meta = $urls['meta'];
        $sha256file = $urls['sha256file'];
        $files = [
            $basedest => $src,
            "$basedest.meta" => $meta,
            "$basedest.sha256" => $sha256file,
        ];
        $retarr = [];
        foreach ($files as $d => $f) {
            $retarr[$d] = ["src" => $f];
            if ($force) {
                @unlink($d);
            }
            if (!file_exists($d)) {
                $retarr[$d]['exists'] = false;
                $retarr[$d]['safeget'] = PhoneBocx::safeGet($d, $f);
            } else {
                $retarr[$d]['exists'] = true;
            }
        }
        $relfile = "$basedest.release";
        file_put_contents($relfile, $urls['releasename']);
        $retarr[$relfile] = ['src' => 'auto', 'exists' => true];
        return $retarr;
    }
}
