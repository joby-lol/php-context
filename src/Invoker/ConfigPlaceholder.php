<?php
/*
 * Context Injection
 * https://github.com/joby-lol/php-context
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\ContextInjection\Invoker;

/**
 * Class for holding the requested config key, category, and valid types of a config key that is being requested for
 * injection, which must be resolved before passing it along. Also holds default value for use if there is no config
 * set for the given key.
 */
readonly class ConfigPlaceholder
{
    /**
     * @param array<string> $valid_types
     */
    public function __construct(
        public string $key,
        public array  $valid_types,
        public bool   $is_optional,
        public mixed  $default,
        public bool $allows_null,
        public string $category = 'default',
    )
    {
    }
}