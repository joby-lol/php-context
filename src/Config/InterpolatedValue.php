<?php

namespace Joby\ContextInjection\Config;

readonly class InterpolatedValue implements ConfigValue
{
    public function __construct(protected string $value)
    {
    }

    public function value(Config $config): string
    {
        return $config->interpolate($this->value);
    }
}