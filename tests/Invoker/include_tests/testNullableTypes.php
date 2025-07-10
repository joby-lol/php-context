<?php
/**
 * This file tests including a file that uses nullable types.
 * 
 * #[ConfigValue("nullable_string_key")]
 * @var ?string $nullable_string
 * 
 * #[ConfigValue("nullable_int_key")]
 * @var int|null $nullable_int
 */
return [
    'nullable_string' => $nullable_string,
    'nullable_int' => $nullable_int
];