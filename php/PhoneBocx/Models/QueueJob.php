<?php

namespace PhoneBocx\Models;

abstract class QueueJob implements QueueJobInterface
{
    /** Set by linkWith, for use by runJob if needed */
    protected string $serobj = "";
    protected int $currentattempts = 0;
    protected ?int $forcedrunafter = null;

    abstract public function runAfter(): int;

    public function forceRunAfter(?int $utime = null)
    {
        $this->forcedrunafter = $utime;
    }

    public function getRef(): string
    {
        return "";
    }

    abstract public function getPackage(): string;

    public function linkWith($obj): self
    {
        $this->serobj = serialize($obj);
        return $this;
    }

    public function getLink()
    {
        if ($this->serobj) {
            return unserialize($this->serobj);
        }
        return null;
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
