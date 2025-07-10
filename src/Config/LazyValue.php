<?php

namespace Joby\ContextInjection\Config;

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