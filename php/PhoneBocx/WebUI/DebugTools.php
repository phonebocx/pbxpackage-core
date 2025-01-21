<?php

namespace PhoneBocx\WebUI;

use PhoneBocx\WebUI\DebugTools\DebugInterface;
use PhoneBocx\WebUI\DebugTools\RebootDevice;

class DebugTools
{
    public static array $tools = [
        "reboot" => ["name" => "Reboot Device", "class" => RebootDevice::class],
    ];

    public static function getToolList()
    {
        return self::$tools;
    }

    public static function getToolPath(string $name)
    {
        $tool = self::$tools[$name];
        return $tool['path'] ?? '?debug=' . $name;
    }

    public static function getToolClassStr(string $name)
    {
        $tool = self::$tools[$name];
        if (!empty($tool['class'])) {
            return " class='" . $tool['class'] . "'";
        }
        return "";
    }

    public static function getToolHtml(string $name)
    {
        $h = "<a href='" . self::getToolPath($name) . "'" . self::getToolClassStr($name) . ">";
        $h .= self::$tools[$name]['name'] . "</a>";
        return $h;
    }

    private bool $isloggedin = false;
    private DebugInterface $handler;
    private array $request;

    public function __construct(array $request)
    {
        $this->request = $request;
        $tool = self::$tools[$request['debug']];
        $this->handler = new $tool['class']($this->request);
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
