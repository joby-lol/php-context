<?php
/*
* smolContext
* https://github.com/joby-lol/smol-context
* (c) 2024-2025 Joby Elliott code@joby.lol
* MIT License https://opensource.org/licenses/MIT
*/

namespace Joby\Smol\Context\Cache\Backends;

use Joby\Smol\Context\Cache\AbstractCacheTestCase;

class EphemeralCacheTestCase extends AbstractCacheTestCase
{
    protected function doCreateCacheInstance(int|null $defaultTtl = null): EphemeralCache
    {
        return new EphemeralCache($defaultTtl ?? 3600);
    }
}