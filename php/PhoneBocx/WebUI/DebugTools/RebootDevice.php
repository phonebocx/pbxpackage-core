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
        print "I would tell loopback to reboot now\n";
        $c = new Client();
        $req = $c->get("http://localhost:4680/reboot");
        print "This is what I saw: '" . $req->getBody() . "'\n";
        return $html;
    }

    public function canRunWithoutLogin(): bool
    {
        return false;
    }

    public function runAsRoot()
    {
        print "Yeah doing the root stuff, called from loopback\n";
        print "I have " . posix_getpid() . " and my parent is " . posix_getppid() . "\n";
        return;
    }
}
