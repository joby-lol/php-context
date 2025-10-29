<?php
/*
 * Context Injection
 * https://github.com/joby-lol/php-context
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\ContextInjection\Cache;

use Joby\ContextInjection\Cache\Backends\EphemeralCache;

class DefaultCache extends CacheWrapper
{
    public function __construct()
    {
        parent::__construct(new EphemeralCache());
    }
}