<?php
/**
 * This file tests including a file that uses multiple variables.
 * 
 * @var \Joby\ContextInjection\TestClasses\TestClassA $test_a
 * @var \Joby\ContextInjection\TestClasses\TestClassB $test_b
 * #[ConfigValue("test_config_key")]
 * @var string $test_value
 */
return [
    'test_a' => $test_a,
    'test_b' => $test_b,
    'test_value' => $test_value
];