<?php

namespace Joby\ContextInjection\Invoker;

use Attribute;

#[Attribute]
readonly class ParameterConfigValue
{
    public function __construct(
        public string $key,
    )
    {
    }
}