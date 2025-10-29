<?php
/*
 * Context Injection
 * https://github.com/joby-lol/php-context
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\ContextInjection\Config;

/**
 * Config value type that can be used for values that are expensive to retrieve or generate, so that they can be loaded
 * only when actually requested.
 */
class LazyValue implements ConfigValue
{
    /**
     * @var callable
     */
    protected readonly mixed $callback;
    protected bool $run = false;
    protected mixed $value;

    public function __construct(
        callable $callback,
    )
    {
        $this->callback = $callback;
    }

    public function value(Config $config): mixed
    {
        if (!$this->run) {
            $this->run = true;
            $this->value = call_user_func($this->callback, $config);
        }
        return $this->value;
    }
}