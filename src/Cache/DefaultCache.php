<?php

namespace Joby\ContextInjection\Cache;

use Joby\ContextInjection\Cache\Backends\EphemeralCache;

class DefaultCache extends CacheWrapper
{
    public function __construct()
    {
        parent::__construct(new EphemeralCache());
    }
}