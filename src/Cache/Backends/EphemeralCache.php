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

namespace Joby\ContextInjection\Cache\Backends;

use DateInterval;

class EphemeralCache extends AbstractCacheBackend
{
    /**
     * Array of cached data, indexed by key, with each value containing a tuple of an expiration timestamp and a
     * cached data value.
     *
     * @var array<string,array{positive-int,mixed}>
     */
    protected array $data = [];

    public function clear(): bool
    {
        $this->data = [];
        return true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($this->data[$key]) && $this->data[$key][0] >= $this->getCurrentTime()) {
            return $this->data[$key][1];
        }
        return $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $ttl = $ttl ?? $this->default_ttl;
        if ($ttl instanceof DateInterval) $ttl = $ttl->format('%s');
        $expires = $this->getCurrentTime() + $ttl;
        $this->data[$key] = [$expires, $value];
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->data[$key]);
        return true;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]) && $this->data[$key][0] >= $this->getCurrentTime();
    }
}