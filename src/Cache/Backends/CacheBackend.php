<?php
/*
 * Context Injection
 * https://github.com/joby-lol/php-context
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\ContextInjection\Cache\Backends;

use Psr\SimpleCache\CacheInterface;

interface CacheBackend extends CacheInterface
{
    /**
     * For testing purposes - allows setting the current time, set to null to use the actual time.
     */
    public function setCurrentTime(?int $time): void;
}