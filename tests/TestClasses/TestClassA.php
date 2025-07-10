<?php

namespace Joby\ContextInjection\TestClasses;

class TestClassA
{
    protected int $id;
    protected static int $id_counter = 0;

    public function __construct()
    {
        $this->id = self::$id_counter++;
    }

    public static function getStaticString(): string
    {
        return 'TestClassA static string';
    }

    public function getInstanceString(): string
    {
        return 'TestClassA instance string';
    }
}
