<?php

namespace Joby\ContextInjection\Cache\Backends;

use Psr\SimpleCache\CacheInterface;

interface CacheBackend extends CacheInterface
{
    /**
     * For testing purposes - allows setting the current time, set to null to use the actual time.
     */
    public function setCurrentTime(?int $time): void;
}