<?php

namespace PhoneBocx\WebUI\DebugTools;

use GuzzleHttp\Client;

class RebootDevice implements DebugInterface
{

    protected array $request;

    public function __construct(array $request)
    {
        $this->request = $request;
    }

    public function updateHtmlArr(array $html): array
    {
        $c = new Client();
        $req = $c->get("http://localhost:4680/reboot");
        print "This should never be reached\n";
        print "'" . $req->getBody() . "'\n";
        return $html;
    }

    public function canRunWithoutLogin(): bool
    {
        return false;
    }

    public function runAsRoot(): bool
    {
        // This should never be seen as reboot -f is RIGHT NOW
        $cmd = "/usr/sbin/reboot -f";
        exec($cmd, $output, $res);
        print "Ran  '$cmd', now have " . json_encode([$res, $output]) . "\n";
        return true;
    }
}
