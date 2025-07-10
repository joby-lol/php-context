<?php

use Joby\ContextInjection\Context;
use Joby\ContextInjection\Invoker\Invoker;

/**
 * Retrieve a service/object from the Pixelpile context injection system.
 * Objects requested this way are lazy-loaded, meaning they are instantiated
 * only when they are first requested. This allows for better performance and
 * memory use.
 *
 * This function is an alias for `Context::get()`, available in the global
 * namespace for convenience and ease of use.
 *
 * @see Context::get() alias
 *
 * @template T<object>
 * @param class-string<T> $class the class of object to retrieve
 * @param string $category the category of the object, if applicable (i.e.
 * "current" to get the current page for a request, etc.)
 * @return T|null
 */
function context(string $class, string $category = 'default'): mixed
{
    return Context::get($class, $category);
}

/**
 * Execute a callable, automatically instantiating any arguments it requires
 * from the Pixelpile context injection system. This allows for easy execution
 * of functions and methods with dependencies, without needing to manually
 * resolve anything.
 *
 * @see Invoker::execute() alias
 *
 * @template T of object
 * @param callable(mixed...):T $fn the callable to execute
 * @return T
 */
function execute(callable $fn): mixed
{
    return Context::get(Invoker::class)->execute($fn);
}
