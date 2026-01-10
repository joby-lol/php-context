<?php

/*
 * smolContext
 * https://github.com/joby-lol/smol-context
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Context\Cache\Backends;

use DateInterval;
use PHPUnit\Framework\TestCase;

class EphemeralCacheTest extends TestCase
{

    private EphemeralCache $cache;

    protected function setUp(): void
    {
        $this->cache = new EphemeralCache(default_ttl: 3600);
        $this->cache->setCurrentTime(1);
    }

    public function testSetGetHasDelete(): void
    {
        $this->assertFalse($this->cache->has('k'));
        $this->assertSame('default', $this->cache->get('k', 'default'));

        $this->assertTrue($this->cache->set('k', 'v', 10));
        $this->assertTrue($this->cache->has('k'));
        $this->assertSame('v', $this->cache->get('k'));

        $this->assertTrue($this->cache->delete('k'));
        $this->assertFalse($this->cache->has('k'));
        $this->assertNull($this->cache->get('k'));
    }

    public function testClear(): void
    {
        $this->cache->set('a', 1, 10);
        $this->cache->set('b', 2, 10);
        $this->assertTrue($this->cache->has('a'));
        $this->assertTrue($this->cache->has('b'));

        $this->assertTrue($this->cache->clear());
        $this->assertFalse($this->cache->has('a'));
        $this->assertFalse($this->cache->has('b'));
    }

    public function testExpirationWithIntTtl(): void
    {
        $this->cache->set('k', 'v', 2);
        $this->assertSame('v', $this->cache->get('k'));

        $this->cache->setCurrentTime(2);
        $this->assertSame('v', $this->cache->get('k'));
        $this->assertTrue($this->cache->has('k'));

        $this->cache->setCurrentTime(4);
        $this->assertNull($this->cache->get('k'));
        $this->assertFalse($this->cache->has('k'));
    }

    public function testDateIntervalTtlSupportsMinutes(): void
    {
        $this->cache->set('k', 'v', new DateInterval('PT2M'));
        $this->assertSame('v', $this->cache->get('k'));

        $this->cache->setCurrentTime(1 + 119);
        $this->assertSame('v', $this->cache->get('k'));

        $this->cache->setCurrentTime(1 + 121);
        $this->assertNull($this->cache->get('k'));
    }

    public function testResettingAKeyRefreshesExpiration(): void
    {
        $this->cache->set('k', 'v1', 10);
        $this->cache->setCurrentTime(6);

        // Refresh at t=6 with another 10s.
        $this->cache->set('k', 'v2', 10);

        $this->cache->setCurrentTime(11); // 5s after refresh
        $this->assertSame('v2', $this->cache->get('k'));

        $this->cache->setCurrentTime(17); // 11s after refresh
        $this->assertNull($this->cache->get('k'));
    }

    public function testGetMultipleSetMultipleDeleteMultipleSmoke(): void
    {
        // These methods are implemented in AbstractCacheBackend, but we verify basic integration here.
        $this->assertTrue($this->cache->setMultiple(['a' => 1, 'b' => 2], 10));
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => null], $this->cache->getMultiple(['a', 'b', 'c']));

        $this->assertTrue($this->cache->deleteMultiple(['a', 'b']));
        $this->assertSame(['a' => null, 'b' => null], $this->cache->getMultiple(['a', 'b']));
    }

}
