<?php

namespace Joby\ContextInjection\Invoker;

use Attribute;

#[Attribute]
readonly class ConfigValue
{
    public function __construct(
        public string $key,
    )
    {
    }
}