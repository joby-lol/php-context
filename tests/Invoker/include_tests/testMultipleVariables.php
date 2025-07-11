<?php
/**
 * This file tests including a file that uses multiple variables.
 *
 * @var TestClassA $test_a
 * @var TestClassB $test_b
 * #[ConfigValue("test_config_key")]
 * @var string                                        $test_value
 */

use Joby\ContextInjection\TestClasses\TestClassA;
use Joby\ContextInjection\TestClasses\TestClassB;

return [
    'test_a' => $test_a,
    'test_b' => $test_b,
    'test_value' => $test_value
];