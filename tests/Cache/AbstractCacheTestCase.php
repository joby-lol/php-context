<?php
/*
* smolContext
* https://github.com/joby-lol/smol-context
* (c) 2024-2025 Joby Elliott code@joby.lol
* MIT License https://opensource.org/licenses/MIT
*/

namespace Joby\Smol\Context\Cache;

use DateInterval;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

abstract class AbstractCacheTestCase extends TestCase
{
    private Backends\CacheBackend $cache;
    private $simulatedTime = 1;

    abstract protected function doCreateCacheInstance(int|null $defaultTtl = 3600): Backends\CacheBackend;

    public function testConstructor()
    {
        $cache = $this->createCacheInstance();
        $this->assertInstanceOf(CacheInterface::class, $cache);
        $cache = $this->createCacheInstance(7200); // Custom default TTL
        $this->assertInstanceOf(CacheInterface::class, $cache);
    }

    public function testSetGet()
    {
        $this->cache->set('foo', 'bar');
        $this->assertEquals('bar', $this->cache->get('foo'));

        // Test with default value for non-existent key
        $this->assertEquals('default', $this->cache->get('nonexistent', 'default'));
        $this->assertNull($this->cache->get('nonexistent'));
    }

    public function testDelete()
    {
        $this->cache->set('foo', 'bar');
        $this->assertTrue($this->cache->delete('foo'));
        $this->assertNull($this->cache->get('foo'));

        // Deleting non-existent key should still return true
        $this->assertTrue($this->cache->delete('nonexistent'));
    }

    public function testClear()
    {
        $this->cache->set('foo', 'bar');
        $this->cache->set('baz', 'qux');

        $this->assertTrue($this->cache->clear());
        $this->assertNull($this->cache->get('foo'));
        $this->assertNull($this->cache->get('baz'));
    }

    public function testHas()
    {
        $this->cache->set('foo', 'bar');
        $this->assertTrue($this->cache->has('foo'));
        $this->assertFalse($this->cache->has('nonexistent'));
    }

    public function testExpiration()
    {
        $this->cache->set('foo', 'bar', 1); // 1 second TTL
        $this->assertEquals('bar', $this->cache->get('foo'));
        $this->assertTrue($this->cache->has('foo'));

        $this->simulateTimePassage(2); // Simulate 2 seconds passing
        $this->assertNull($this->cache->get('foo'));
        $this->assertFalse($this->cache->has('foo'));
    }

    public function testDateIntervalTtl()
    {
        $interval = new DateInterval('PT2S'); // 2 seconds
        $this->cache->set('foo', 'bar', $interval);
        $this->assertEquals('bar', $this->cache->get('foo'));

        $this->simulateTimePassage(3); // Simulate 3 seconds passing
        $this->assertNull($this->cache->get('foo'));
    }

    public function testGetMultiple()
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');

        $values = $this->cache->getMultiple(['key1', 'key2', 'nonexistent']);
        $expected = [
            'key1' => 'value1',
            'key2' => 'value2',
            'nonexistent' => null,
        ];

        $this->assertEquals($expected, $values);

        // Test with default value
        $values = $this->cache->getMultiple(['key1', 'nonexistent'], 'default');
        $expected = [
            'key1' => 'value1',
            'nonexistent' => 'default',
        ];
        $this->assertEquals($expected, $values);
    }

    public function testSetMultiple()
    {
        $values = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $this->assertTrue($this->cache->setMultiple($values));
        $this->assertEquals('value1', $this->cache->get('key1'));
        $this->assertEquals('value2', $this->cache->get('key2'));
    }

    public function testDeleteMultiple()
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');

        $this->assertTrue($this->cache->deleteMultiple(['key1', 'key2', 'nonexistent']));
        $this->assertNull($this->cache->get('key1'));
        $this->assertNull($this->cache->get('key2'));
    }

    public function testDefaultTtl()
    {
        $cache = $this->createCacheInstance(1); // 1 second default TTL
        $cache->set('foo', 'bar');              // Using default TTL
        $this->assertEquals('bar', $cache->get('foo'));

        $this->simulateTimePassage(2);
        $this->assertNull($cache->get('foo'));
    }

    public function testNullTtlUsesDefault()
    {
        $cache = $this->createCacheInstance(1); // 1 second default TTL
        $cache->set('foo', 'bar', null);
        $this->assertEquals('bar', $cache->get('foo'));

        $this->simulateTimePassage(2);
        $this->assertNull($cache->get('foo'));
    }

    protected function setUp(): void
    {
        $this->createCacheInstance();
    }

    protected function createCacheInstance(int|null $defaultTtl = 3600): Backends\CacheBackend
    {
        $this->cache = $this->doCreateCacheInstance($defaultTtl);
        $this->simulatedTime = 1;
        $this->cache->setCurrentTime($this->simulatedTime);
        return $this->cache;
    }

    /**
     * Simulate the passage of time to trigger cache expiration
     *
     * @param int $seconds Number of seconds to simulate passing
     */
    protected function simulateTimePassage(int $seconds): void
    {
        $this->simulatedTime += $seconds;
        $this->cache->setCurrentTime($this->simulatedTime);
    }
}