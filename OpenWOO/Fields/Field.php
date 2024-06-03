<?php

namespace OpenWOO\Fields;

abstract class Field
{
    protected string $key;
    protected $value;

    public function __construct(string $key, $value)
    {
        $this->key   = $key;
        $this->value = $value;
    }

    public static function make(string $key, $value): self
    {
        return new static($key, $value);
    }

    /**
     * @return mixed
     */
    abstract public function get();
}
