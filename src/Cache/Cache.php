<?php

/**
 * Context Injection: https://codeberg.org/joby/php-context
 * MIT License: Copyright (c) 2025 Joby Elliott
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Joby\ContextInjection\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * This cache interface is a slight modification to the PHP FIG Simple Cache. It is compatible and should be usable
 * anywhere the FIG standard is requested. The only real modification is the addition of the `cache()` method for doing
 * ergonomic one-step cache get/set operations.
 *
 * It is also noteworthy when comparing this interface to the FIG standard that the `cache()` method MAY defer
 * execution of the callable and return stale data if it is available, and the implementation supports deferred
 * background execution.
 */
interface Cache extends CacheInterface
{
    /**
     * One-stop helper function to get a value if it exists, or run a callable to instantiate it if it is not found.
     * Implementations MAY defer executing the callable as a background job and return stale data.
     *
     * @template T
     * @param string                $key
     * @param callable():T          $callback
     * @param int|DateInterval|null $ttl
     *
     * @return T
     *
     * @throws InvalidArgumentException MUST be thrown if the $key string is not a legal value.
     */
    public function cache(string $key, callable $callback, null|int|DateInterval $ttl = null): mixed;

    /**
     * Fetches a value from the cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws InvalidArgumentException MUST be thrown if the $key string is not a legal value.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                $key    The key of the item to store.
     * @param mixed                 $value  The value of the item to store, must be serializable.
     * @param null|int|DateInterval $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws InvalidArgumentException MUST be thrown if the $key string is not a legal value.
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool;

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws InvalidArgumentException MUST be thrown if the $key string is not a legal value.
     */
    public function delete(string $key): bool;

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear(): bool;

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable<string> $keys    A list of keys that can be obtained in a single operation.
     * @param mixed            $default Default value to return for keys that do not exist.
     *
     * @return iterable<string, mixed> A list of key => value pairs. Cache keys that do not exist or are stale will
     *                          have $default as value.
     *
     * @throws InvalidArgumentException MUST be thrown if $keys is neither an array nor a Traversable, or if any of the
     *                                  $keys are not a legal value.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable;

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable              $values  A list of key => value pairs for a multiple-set operation.
     * @param null|int|DateInterval $ttl     Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws InvalidArgumentException MUST be thrown if $values is neither an array nor a Traversable, or if any of
     *                                  the $values are not a legal value.
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool;

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable<string> $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws InvalidArgumentException MUST be thrown if $values is neither an array nor a Traversable, or if any of
     *                                  the $values are not a legal value.
     */
    public function deleteMultiple(iterable $keys): bool;

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws InvalidArgumentException MUST be thrown if the $key string is not a legal value.
     */
    public function has(string $key): bool;
}