<?php

namespace Joby\ContextInjection\Cache\Backends;

use Joby\ContextInjection\Cache\AbstractCacheTestCase;

class EphemeralCacheTestCase extends AbstractCacheTestCase
{
    protected function doCreateCacheInstance(int|null $defaultTtl = null): EphemeralCache
    {
        return new EphemeralCache($defaultTtl ?? 3600);
    }
}