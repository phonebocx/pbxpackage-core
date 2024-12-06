<?php

namespace PhoneBocx\Models;

class HookModel extends GenericModel
{
    protected array $callables = [];

    public function addCallable($f): self
    {
        $this->callables[] = $f;
        return $this;
    }

    public function hasCallable(): bool
    {
        return (!empty($this->callables));
    }

    public function getCallables(): array
    {
        return $this->callables;
    }
}
