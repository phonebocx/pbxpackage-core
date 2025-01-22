<?php

namespace PhoneBocx\WebUI\DebugTools;

use GuzzleHttp\Client;
use PhoneBocx\AsRoot\RemountConf;

class DelOldSiteconf implements DebugInterface
{
    protected static string $filename = "/run/live/medium/oldsiteconf";

    protected array $request;

    public function __construct(array $request)
    {
        $this->request = $request;
    }

    public static function shouldBeShown(): bool
    {
        return file_exists(self::$filename);
    }

    public function updateHtmlArr(array $html): array
    {
        header("Location: /");
        $c = new Client();
        $req = $c->get("http://localhost:4680/delsiteconf");
        print "Redirecting to /\n";
        print "'" . $req->getBody() . "'\n";
        exit;
    }

    public function canRunWithoutLogin(): bool
    {
        return false;
    }

    public function runAsRoot(): bool
    {
        $c = new RemountConf();
        $c->mountRw();
        print "Deleting " . self::$filename . "\n";
        unlink(self::$filename);
        return true;
    }
}
