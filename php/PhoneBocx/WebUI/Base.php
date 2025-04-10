<?php

namespace PhoneBocx\WebUI;

class Base
{
    public function getResponse(): array
    {
        return ["data", "other data", "stuff"];
    }

    public function respond(bool $string = false)
    {
        header("Content-Type: application/json");
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("X-Powered-By: 'Honest' Rob");
        if ($string) {
            return json_encode($this->getResponse());
        }
        return $this->getResponse();
    }
}
