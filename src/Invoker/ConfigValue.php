<?php
/*
 * Context Injection
 * https://github.com/joby-lol/php-context
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Context\Invoker;

use Attribute;

/**
 * Attribute used to indicate that an injected argument should be a value taken from the main Config
 */
#[Attribute]
readonly class ConfigValue
{
    public function __construct(
        public string $key,
    )
    {
    }
}