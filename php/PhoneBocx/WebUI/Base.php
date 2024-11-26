<?php

namespace PhoneBocx\WebUI;

class Base
{

    public function getResponse(): array
    {
        return ["data", "other data", "stuff"];
    }

    public function respond()
    {
        header("Content-Type: application/json");
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("X-Powered-By: FaxYeeter/v4");
        print json_encode($this->getResponse());
    }
}
