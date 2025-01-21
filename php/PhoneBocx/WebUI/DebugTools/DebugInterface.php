<?php

namespace PhoneBocx\WebUI\DebugTools;

/** @package PhoneBocx\WebUI\DebugTools */
interface DebugInterface
{
    public function __construct(array $request);

    /**
     * Can this be run without being logged in?
     * 
     * @return bool
     */
    public function canRunWithoutLogin(): bool;

    public function updateHtmlArr(array $html): array;

    public function runAsRoot();
}
