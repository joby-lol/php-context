<?php

namespace Joby\ContextInjection\Invoker;

use Attribute;

/**
 * Attribute to manually override the value of a parameter if it needs to be
 * generated in a way that cannot be resolved by the context injection system.
 * For example, if a parameter needs a specific value that comes from an outside
 * source, or if it needs to be a specific object not registered in the
 * context system.
 *
 * It is designed to use a callback that returns the value to be passed into
 * this parameter so that it can be resolved at runtime for better performance
 * and flexibility.
 *
 * @template T of mixed
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class ParameterValue
{
    /**
     * The callback to execute to get the value of the parameter.
     *
     * @param callable(mixed...): T $callback The callback to execute, or a value
     */
    public function __construct(
        protected mixed $callback,
    )
    {
    }

    /**
     * Get the value of the parameter by executing the callback.
     *
     * @return T The value returned by the callback.
     */
    public function getValue(): mixed
    {
        return call_user_func($this->callback);
    }
}
