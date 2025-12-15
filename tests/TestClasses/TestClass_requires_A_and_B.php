<?php

namespace Joby\Smol\Context\TestClasses;

class TestClass_requires_A_and_B
{
    protected static int $id_counter = 0;
    protected int $id;

    public function __construct(
        public readonly TestClassA $a,
        public readonly TestClassB $b
    )
    {
        $this->id = self::$id_counter++;
    }
}
