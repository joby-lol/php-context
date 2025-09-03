<?php

namespace Joby\ContextInjection\Invoker;

use RuntimeException;
use Throwable;

class ExecutionException extends RuntimeException
{
    public function __construct(Throwable $previous)
    {
        parent::__construct(
            sprintf('Exception of type %s thrown during execution: %s', get_class($previous), $previous->getMessage()),
            previous: $previous
        );
    }
}