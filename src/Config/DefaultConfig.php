<?php

namespace Joby\ContextInjection\Config;

class DefaultConfig implements Config
{
    protected array $defaults = [];
    protected array $values = [];

    public function __construct(
        array $defaults = [],
        array $values = []
    )
    {
        $this->defaults = $defaults;
        $this->values = $values;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values)
            || array_key_exists($key, $this->defaults);
    }

    public function get(string $key): mixed
    {
        return $this->values[$key]
            ?? $this->defaults[$key];
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function unset(string $key): void
    {
        unset($this->values[$key]);
    }
}