<?php
/*
* Context Injection
* https://github.com/joby-lol/php-context
* (c) 2024-2025 Joby Elliott code@joby.lol
* MIT License https://opensource.org/licenses/MIT
*/

namespace Joby\ContextInjection\Cache\Backends;

use Joby\ContextInjection\Cache\AbstractCacheTestCase;

class EphemeralCacheTestCase extends AbstractCacheTestCase
{
    protected function doCreateCacheInstance(int|null $defaultTtl = null): EphemeralCache
    {
        return new EphemeralCache($defaultTtl ?? 3600);
    }
}