<?php

namespace PhoneBocx\Models;

abstract class QueueJob implements QueueJobInterface
{
    protected string $serobj = "";
    protected int $currentattempts = 0;

    abstract public function runAfter(): int;

    abstract public function getRef(): string;

    abstract public function getPackage(): string;

    abstract public function getHandler(): string;

    public function serializeObj($obj)
    {
        $this->serobj = serialize($obj);
    }

    public function getFailureBackoff(): int
    {
        return 12;
    }

    public function maxAttempts(): int
    {
        return 5;
    }

    public function getCurrentAttempts(): int
    {
        return $this->currentattempts;
    }

    public function incrementAttempts(): int
    {
        $this->currentattempts++;
        return $this->currentattempts;
    }

    public function onSuccess()
    {
        return;
    }

    public function onFailure(string $reason = "")
    {
        print "Failure because of '$reason'\n";
    }

    public function onFatal(string $reason =  "")
    {
        print "Fatal because of '$reason'\n";
    }
}
