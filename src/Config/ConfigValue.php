<?php

namespace Joby\ContextInjection\Config;

/**
 * Wrapper for allowing more complex config values, such as interpolated (@see InterpolatedValue)
 * or lazy values (@see LazyValue).
 * This wrapper is also necessary if a value is actually supposed to be null (@see NullValue)
 *
 * ConfigValue objects may be nested indefinitely, and they will all be unwrapped until a value comes out.
 */
interface ConfigValue
{
    function value(Config $config): mixed;
}