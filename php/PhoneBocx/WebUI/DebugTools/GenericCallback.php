<?php

namespace PhoneBocx\WebUI\DebugTools;

class GenericCallback implements DebugInterface
{
    protected array $request;

    public function __construct(array $request)
    {
        $this->request = $request;
    }

    public function updateHtmlArr(array $html): array
    {
        return $html;
    }

    public function canRunWithoutLogin(): bool
    {
        return true;
    }

    public function runAsRoot(): bool
    {
        print array_shift($this->request);
        return false;
    }
}
