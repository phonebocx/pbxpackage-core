<?php

namespace PhoneBocx\WebUI;

use PhoneBocx\WebUI\DebugTools\ConsolePng;
use PhoneBocx\WebUI\DebugTools\DebugInterface;
use PhoneBocx\WebUI\DebugTools\DelOldSiteconf;
use PhoneBocx\WebUI\DebugTools\RebootDevice;

class DebugTools
{
    public static array $tools = [
        "console" => ["name" => "Show Console", "obj" => ConsolePng::class, "htmlfromobj" => true],
        "reboot" => ["name" => "Reboot Device", "obj" => RebootDevice::class],
        "siteconf" => ["name" => "Cleanup from Upgrade", "obj" => DelOldSiteconf::class],
    ];

    public static function getToolList()
    {
        $retarr = self::$tools;
        foreach ($retarr as $name => $row) {
            $c = $row['obj'];
            if (!$c::shouldBeShown()) {
                unset($retarr[$name]);
            }
        }
        return $retarr;
    }

    public static function getToolPath(string $name)
    {
        $tool = self::$tools[$name];
        return $tool['path'] ?? '?debug=' . $name;
    }

    public static function getToolLinkParams(string $name)
    {
        $params = "";
        $tool = self::$tools[$name];
        if (!empty($tool['class'])) {
            $params = " class='" . $tool['class'] . "'";
        }
        if (!empty($tool['id'])) {
            $params = " id='" . $tool['id'] . "'";
        }
        if (!empty($tool['onclick'])) {
            $params = " onclick='" . $tool['onclick'] . "'";
        }
        return $params;
    }

    public static function getToolHtml(string $name)
    {
        if (!empty(self::$tools[$name]['htmlfromobj'])) {
            $obj = self::$tools[$name]['obj'];
            return $obj::getLineHtml();
        }
        $h = "<a href='" . self::getToolPath($name) . "'" . self::getToolLinkParams($name) . ">";
        $extra = self::$tools[$name]['extra'] ?? "";
        $h .= self::$tools[$name]['name'] . "</a> $extra";
        return $h;
    }

    private bool $isloggedin = false;
    private DebugInterface $handler;
    private array $request;

    public function __construct(array $request)
    {
        $this->request = $request;
        $tool = self::$tools[$request['debug']];
        $this->handler = new $tool['obj']($this->request);
    }

    public function setLoggedIn(bool $isloggedin)
    {
        $this->isloggedin = $isloggedin;
    }

    public function updateHtmlArr(array $html)
    {
        if (!$this->isloggedin) {
            if (!$this->handler->canRunWithoutLogin()) {
                print "Not logged in\n";
                exit;
                return $html;
            }
        }
        return $this->handler->updateHtmlArr($html);
    }
}
