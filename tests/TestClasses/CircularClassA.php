<?php

namespace Joby\ContextInjection\TestClasses;

class CircularClassA
{
    protected static int $id_counter = 0;
    protected int $id;

    public function __construct(
        public readonly CircularClassB $b
    )
    {
        $this->id = self::$id_counter++;
    }
}