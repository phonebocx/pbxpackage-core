<?php

namespace PhoneBocx\WebUI\DebugTools;

use GuzzleHttp\Client;
use PhoneBocx\AsRoot\ConsoleScreenshot;

class ConsolePng implements DebugInterface
{

    protected array $request;
    protected static $pngurl = '/core/api/screenshot?png=file';

    public function __construct(array $request)
    {
        $this->request = $request;
    }

    public static function shouldBeShown(): bool
    {
        return true;
    }

    public static function runClient(bool $force = false): array
    {
        $c = new Client();
        $url = "http://localhost:4680/consolepng";
        if ($force) {
            $url .= "?force=true";
        }
        $req = $c->get($url);
        $retarr = [
            "url" => $url,
            "result" => $req->getStatusCode(),
            "body" => json_decode((string) $req->getBody(), true),
        ];
        return $retarr;
    }

    public function updateHtmlArr(array $html): array
    {
        // Nothing added here, it's manually imported from coredebug_webhook
        return $html;
    }

    // Loaded in coredebug_webhook
    public static function getDebugPageHtml(): string
    {
        $str = "<div id='consolediv'><div id='console'></div></div>\n";
        return $str;
    }

    // Loaded in coredebug_webhook
    public static function getConsoleJavascript(): string
    {
        $js = '
        window.pollhook = function(d) {
          window.consoleneedsrefresh = d.consoleneedsrefresh;
          if (d.consoleage) {
            s = document.getElementById("consoleage");
            s.innerHTML=d.consoleage;
          }
          console.log("I was called", d.consoleage, window.consoleneedsrefresh);
        };
        function updateConsole() { 
          if (window.consoleneedsrefresh) {
            i = "<img id=consolepng src=\'' . self::$pngurl . '&force=true\'>";
          } else {
            i = "<img id=consolepng src=\'' . self::$pngurl . '\'>";
          }
          c = document.getElementById("console");
          if (c.innerHTML) {
            // Delete it
            c.innerHTML="";
            console.log("hidden");
          } else {
            document.getElementById("console").innerHTML=i;
            console.log("shown");
          }
        }';
        return $js;
    }

    public function canRunWithoutLogin(): bool
    {
        return false;
    }

    public function runAsRoot(): bool
    {
        $c = new ConsoleScreenshot(false);
        $force = (!empty($_REQUEST['force']));
        if ($force) {
            $retarr = $c->captureScreenshot(-1);
        } else {
            $retarr = $c->captureScreenshot();
        }
        $retarr["rarrequest"] = $_REQUEST;
        print json_encode($retarr) . "\n";
        return true;
    }
}
