<?php

namespace PhoneBocx\WebUI\DebugTools;

/** @package PhoneBocx\WebUI\DebugTools */
interface DebugInterface
{
    public static function shouldBeShown(): bool;

    public function __construct(array $request);

    /**
     * Can this be run without being logged in?
     * 
     * @return bool
     */
    public function canRunWithoutLogin(): bool;

    public function updateHtmlArr(array $html): array;

    /**
     * If this returns false, loopback should not
     * self-kill the current process.
     *
     * @return boolean
     */
    public function runAsRoot(): bool;
}
