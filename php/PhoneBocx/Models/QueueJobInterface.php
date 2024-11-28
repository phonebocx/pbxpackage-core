<?php

namespace PhoneBocx\Models;

interface QueueJobInterface
{
    public function runAfter(): int;

    public function getPackage(): string;

    public function getRef(): string;

    public function serializeObj($obj);

    public function getHandler(): string;

    public function runJob(): bool;

    public function getFailureBackoff(): int;

    public function maxAttempts(): int;

    public function getCurrentAttempts(): int;

    public function incrementAttempts(): int;

    public function onSuccess();
    public function onFailure(string $reason = "");
    public function onFatal(string $reason = "");
}
