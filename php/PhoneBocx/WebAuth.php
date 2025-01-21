<?php

namespace PhoneBocx;

class WebAuth
{

    public $needsauth = false;
    public $authvals;
    private static $me;
    public static $isloggedin = false;

    public static function create()
    {
        if (!self::$me) {
            self::$me = new self;
        }
        return self::$me;
    }

    // Declare this private so it can't be instantiated accidentally
    private function __construct()
    {
        $this->authvals = [];
        $vals = $this->getAuthValues();
        foreach ($vals as $v) {
            if (preg_match("/(.+):(.+)/", $v, $out)) {
                $this->needsauth = true;
                $this->authvals[$out[1]] = $out[2];
            }
        }
    }

    public static function isLoggedIn()
    {
        $a = self::create();
        if (!$a->needsauth) {
            self::$isloggedin = false;
            return true;
        }
        $user = $_SERVER['PHP_AUTH_USER'] ?? "";
        $pass = $_SERVER['PHP_AUTH_PW'] ?? "";
        if ($user === "admin" && $a->checkAdminPass($pass)) {
            self::$isloggedin = true;
            return true;
        }
        if ($user && !empty($a->authvals[$user])) {
            // User exists. If this is a hashed password (first char is $), use password_verify()
            // on the value, otherwise a string comparison.
            $hashed = $a->authvals[$user];
            if ($hashed[0] === "$") {
                if (password_verify($pass, $hashed)) {
                    self::$isloggedin = true;
                    return true;
                }
            } else {
                if ($pass === $a->authvals[$user]) {
                    self::$isloggedin = true;
                    return true;
                }
            }
        }
        // Auth failed.
        header('WWW-Authenticate: Basic realm="PhoneBo.cx Device"');
        header('HTTP/1.0 401 Unauthorized');
        print "This device is configured to require password authentication.\n";
        exit;
    }

    public static function logOut()
    {
        $user = $_SERVER['PHP_AUTH_USER'] ?? "";
        if ($user) {
            header('WWW-Authenticate: Basic realm="PhoneBo.cx Device"');
            header('HTTP/1.0 401 Unauthorized');
            print "<html><body><p>You have been logged out. <a href='/'>Click Here</a> to log in</p></body></html>\n";
            exit;
        }
        header('Location: /');
        header('HTTP/1.0 302 Found');
        exit;
    }

    public function getAuthValues()
    {
        $m = PhoneBocx::create();
        if (!$m->getKey('httpauth')) {
            return [];
        }
        $retarr = json_decode(base64_decode($m->getKey('httpauth')), true);
        if (!$retarr) {
            return [];
        }
        return $retarr;
    }

    public function checkAdminPass($pass)
    {
        if (!is_readable("/sys/class/dmi/id/product_uuid")) {
            print "Can't admin login, device password not readable\n";
            exit;
        }
        $uuid = trim(file_get_contents("/sys/class/dmi/id/product_uuid"));
        if (strlen($uuid) != 36) {
            print "Can't admin login, device password length wrong\n";
            exit;
        }
        return ($pass === $uuid);
    }
}
