<?php

namespace PhoneBocx\Models;

use Iterator;
use JsonSerializable;

class GenericModel implements JsonSerializable, Iterator
{
    protected $pos = 0;

    protected $data = [];

    protected $ignorekeys = [];

    public function __construct(array $settings = [])
    {
        $this->data = $settings;
    }

    function rewind(): void
    {
        reset($this->data);
    }

    function current(): mixed
    {
        return current($this->data);
    }

    function key(): mixed
    {
        return key($this->data);
    }

    function next(): void
    {
        next($this->data);
    }

    function valid(): bool
    {
        return key($this->data) !== null;
    }

    public function jsonSerialize(): mixed
    {
        return $this->data;
    }

    /**
     * @param mixed $k
     * @param mixed $v
     * @return void
     */
    public function __set($k, $v)
    {
        $this->data[$k] = $v;
    }

    /**
     * @param mixed $k
     * @return mixed
     * @throws \Exception
     */
    public function &__get($k)
    {
        if (!array_key_exists($k, $this->data)) {
            throw new \Exception("No $k in this model - only have " . join(",", array_keys($this->data)));
        }
        return $this->data[$k];
    }
}
