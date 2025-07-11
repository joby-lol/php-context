<?php
/**
 * This file tests including a file that uses a non-fully-qualified class name.
 *
 * @var TB $test
 */

use Joby\ContextInjection\TestClasses\TestClassB as TB;

return $test;