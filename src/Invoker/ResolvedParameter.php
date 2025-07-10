<?php

namespace Joby\ContextInjection\Invoker;

class ResolvedParameter
{
    public function __construct(
        public readonly mixed $value
    )
    {
    }
}
