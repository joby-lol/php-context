<?php

namespace Joby\ContextInjection\TestClasses;

class TestClassB
{
    protected static int $id_counter = 0;
    protected int $id;

    public function __construct()
    {
        $this->id = self::$id_counter++;
    }
}