<?php

namespace PhoneBocx\API;

class Base
{

    public function getResponse(): array
    {
        return ["data", "other data", "stuff"];
    }

    public function respond()
    {
        header("Content-Type: application/json");
        print json_encode($this->getResponse());
    }
}
