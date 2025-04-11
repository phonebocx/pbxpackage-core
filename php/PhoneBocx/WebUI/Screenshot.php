<?php

namespace PhoneBocx\WebUI;

use PhoneBocx\AsRoot\ConsoleScreenshot;
use PhoneBocx\WebUI\DebugTools\ConsolePng;

class Screenshot extends Base
{
    public function getPngFile()
    {
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("X-Powered-By: FaxYeeter/v4");

        if (isset($_REQUEST['force'])) {
            header("X-Screenshot-Refresh: " . base64_encode(json_encode($this->getResponse())));
        }
        $console = new ConsoleScreenshot(true);
        $png = $console->getScreenshotFile();
        $age = $console->getScreenshotAge();
        if ($age === null) {
            http_response_code(404);
            header("Content-Type: application/json");
            print json_encode(["error" => true, "message" => "age is null?", "age" => $age]);
            exit;
        }
        header("Content-Type: image/png");
        header("Content-Transfer-Encoding: Binary");
        header("Content-Length:" . filesize($png));
        readfile($png);
        exit;
    }

    public function respond(bool $string = false)
    {
        if (!isset($_REQUEST['png'])) {
            return parent::respond($string);
        }
        return $this->getPngFile();
    }

    public function getResponse(): array
    {
        $console = new ConsoleScreenshot(true);
        $png = $console->getScreenshotFile();
        $age = $console->getScreenshotAge();
        $retarr = [
            "png" => $png,
            "age" => $age,
        ];
        // Do we need to update it?
        if (isset($_REQUEST['force'])) {
            $force = true;
        } else {
            $force = false;
        }
        if ($force || $console->needsRefresh()) {
            $retarr["updateneeded"] = true;
            // Trigger an update
            try {
                $res = ConsolePng::runClient($force);
                $retarr["updated"] = $res;
            } catch (\Exception $e) {
                $retarr["updatefailed"] = $e->getMessage();
            }
        } else {
            $retarr["updateneeded"] = false;
        }
        return $retarr;
    }
}
