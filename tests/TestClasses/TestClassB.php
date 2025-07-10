<?php

namespace Joby\ContextInjection\TestClasses;

class TestClassB {
    protected int $id;
    protected static int $id_counter = 0;

    public function __construct()
    {
        $this->id = self::$id_counter++;
    }
}