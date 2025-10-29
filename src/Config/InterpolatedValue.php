<?php
/*
 * Context Injection
 * https://github.com/joby-lol/php-context
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\ContextInjection\Config;

/**
 * Indicates a config value that must have other values interpolated. Values must be explicitly set as
 * InterpolatedValue objects at the time they are set for interpolation to happen.
 */
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