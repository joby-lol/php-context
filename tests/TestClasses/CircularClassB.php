<?php

namespace Joby\ContextInjection\TestClasses;

class CircularClassB
{
    protected int $id;
    protected static int $id_counter = 0;

    public function __construct(
        public readonly CircularClassA $a
    ) {
        $this->id = self::$id_counter++;
    }
}