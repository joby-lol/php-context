<?php
/*
 * Context Injection
 * https://github.com/joby-lol/php-context
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\ContextInjection\Config;

/**
 * A value that resolves to null. Generally this is only used in locators to indicate that the return value should be
 * null, to differentiate from those locators returning an actual null value to indicate that they did not find a value.
 */
class NullValue implements ConfigValue
{
    public function value(Config $config): null
    {
        return null;
    }
}