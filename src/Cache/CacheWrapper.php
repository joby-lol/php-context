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

/**
 * A wrapper allowing any implementation of the FIG Simple Cache PSR to be wrapped and used as a
 * ContextInjection-compatible cache.
 */
class CacheWrapper implements Cache
{
    public function __construct(
        protected CacheInterface $backend
    )
    {
    }

    /**
     * Get a value from the underlying system if it exists, or execute the callback to generate and set it if necessary.
     *
     * @param string                $key
     * @param callable              $callback
     * @param DateInterval|int|null $ttl
     *
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function cache(string $key, callable $callback, DateInterval|int|null $ttl = null): mixed
    {
        $value = $this->backend->get($key, new NoValue());
        if ($value instanceof NoValue) {
            $value = $callback();
            $this->backend->set($key, $value, $ttl);
        }
        return $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->backend->get($key, $default);
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        return $this->backend->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->backend->delete($key);
    }

    public function clear(): bool
    {
        return $this->backend->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->backend->getMultiple($keys, $default);
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        return $this->backend->setMultiple($values, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->backend->deleteMultiple($keys);
    }

    public function has(string $key): bool
    {
        return $this->backend->has($key);
    }
}