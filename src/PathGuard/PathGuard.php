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

namespace Joby\ContextInjection\PathGuard;

/**
 * Generic interface for classes that are used to check if a file is allowed to be used in some way, be that including,
 * reading, writing, etc. The default implementations are designed to be simple and easy to use, but you can create your
 * own. The built-in interfaces based on this one are:
 *
 * * IncludeGuard (for checking if a file can be included, used in the Invoker when including files)
 * * ReadGuard (for checking if a file is allowed to be read)
 * * WriteGuard (for checking if a file is allowed to be written)
 *
 * File rules take precedence over directory rules, and after that deny rules take precedence over allow rules. This
 * means that you can allow a directory, but deny files or subdirectories within it. It also means that you can deny a
 * directory, but allow specific files within it.
 */
interface PathGuard
{
    public function check(string $filename): bool;

    public function allowDirectory(string $directory): void;

    public function denyDirectory(string $directory): void;

    public function allowFile(string $file): void;

    public function denyFile(string $file): void;
}