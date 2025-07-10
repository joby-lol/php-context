<?php

namespace Joby\ContextInjection\TestClasses;

class TestClass_requires_A_and_B
{
    protected int $id;
    protected static int $id_counter = 0;

    public function __construct(
        public readonly TestClassA $a,
        public readonly TestClassB $b
    ) {
        $this->id = self::$id_counter++;
    }
}
