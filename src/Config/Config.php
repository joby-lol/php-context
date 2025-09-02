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

namespace Joby\ContextInjection\Config;

/**
 * Interface for a unified configuration management system that can be integrated with Context/Container. It's basically
 * a key/value store, with one additional method to interpolate values into strings by name.
 */
interface Config
{
    /**
     * Determine whether a config option exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get a value from config using its string key.
     * Throws an exception if it doesn't exist.
     *
     * @param string $key
     *
     * @return mixed
     * @throws ConfigException
     */
    public function get(string $key): mixed;

    /**
     * Sets a config value.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Unsets a config value.
     *
     * @param string $key
     *
     * @return void
     */
    public function unset(string $key): void;

    /**
     * Interpolates the given string value by processing any placeholders or variables within it.
     * Placeholders should be in the form `${config_key}`.
     * If any placeholders do not exist or are non-scalar an exception will be thrown.
     *
     * @param string $value The input string containing placeholders or variables to be interpolated.
     *
     * @return string The resulting string after interpolation.
     * @throws ConfigException
     */
    public function interpolate(string $value): string;
}