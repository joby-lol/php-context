<?php
/**
 * This file tests that an exception is thrown when a var declaration is included with a union type. Including this
 * file should generate an exception.
 *
 * @var TestClassA|string $test
 */

use Joby\Smol\Context\TestClasses\TestClassA;

return $test;