<?php

namespace Joby\ContextInjection\TestClasses;

class CircularClassB
{
    protected static int $id_counter = 0;
    protected int $id;

    public function __construct(
        public readonly CircularClassA $a
    )
    {
        $this->id = self::$id_counter++;
    }
}