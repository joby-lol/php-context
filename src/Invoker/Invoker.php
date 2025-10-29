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

namespace Joby\ContextInjection\Invoker;

use Joby\ContextInjection\Container;

/**
 * Interface for the class the content injection system uses to instantiate
 * objects and execute functions with dependencies. Defining this as an
 * interface is useful because it means you can "easily" swap it out if you want
 * to extend the capabilities of the context injection system. For example, if
 * you wanted to add your own custom parameter attributes or change how
 * dependencies are resolved, you could implement your own version of this
 * interface and use that instead. This allows the basic context container to
 * remain simple and focused on its core functionality, while still allowing for
 * near-total modification.
 */
interface Invoker
{
    public function __construct(Container $container);

    /**
     * Instantiate a class of the given type, resolving all its dependencies
     * using the context injection system.
     *
     * @template T of object
     * @param class-string<T> $class
     *
     * @return T
     *
     * @throws InstantiationException if an error occurs while instantiating the class
     */
    public function instantiate(string $class): object;

    /**
     * Execute a callable, automatically instantiating any arguments it requires from the context injection system.
     * This allows for easy execution of functions and methods with dependencies, without needing to manually resolve
     * anything.
     *
     * @template T of mixed
     * @param callable(mixed...):T $fn
     *
     * @return T
     *
     * @throws ExecutionException if an error occurs while executing the callable
     */
    public function execute(callable $fn): mixed;

    /**
     * Include a given file, parsing for an opening docblock and resolving var tags as if they
     * were dependencies to be loaded from the container.
     *
     * Because docblock tags don't support Attributes, their equivalents are just parsed as strings.
     * Core attributes are available by inserting strings that look like them on lines preceding a var tag. The
     * actual Attribute classes need not be included, because this system just looks for strings that
     * look like `#[CategoryName("category_name")]` or `[ConfigValue("config_key")]`.
     *
     * This method will return either the output of the included file, or the value returned by it if there is one.
     * Note that if the included script explicitly returns the integer "1" that cannot be differentiated from returning
     * nothing at all. Generally the best practice is to return objects if you are returning anything, for unambiguous
     * behavior. Although non-integer values are also a reasonable choice.
     *
     * @throws IncludeException if an error occurs while including the file
     */
    public function include(string $file): mixed;
}