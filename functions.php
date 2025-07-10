<?php

use Joby\ContextInjection\Context;
use Joby\ContextInjection\Invoker\Invoker;

/**
 * Retrieve a service/object from the context injection system.
 * Objects requested this way are lazy-loaded, meaning they are instantiated
 * only when they are first requested. This allows for better performance and
 * memory use.
 *
 * This function is an alias for `Context::get()`, available in the global
 * namespace for convenience and ease of use.
 *
 * @template T<object>
 * @param class-string<T> $class the class of object to retrieve
 * @param string $category the category of the object, if applicable (i.e. "current" to get the current page for a request, etc.)
 * @return T|null
 */
function ctx(string $class, string $category = 'default'): mixed
{
    return Context::get($class, $category);
}

/**
 * Execute a callable, automatically instantiating any arguments it requires
 * from the context injection system. This allows for easy execution
 * of functions and methods with dependencies, without needing to manually
 * resolve anything.
 *
 * @template T of object
 * @param callable(mixed...):T $fn the callable to execute
 * @return T
 */
function ctx_execute(callable $fn): mixed
{
    return Context::get(Invoker::class)->execute($fn);
}

/**
 * Include a given file, parsing for an opening docblock and resolving var tags as if they
 *  were dependencies to be loaded from the container.
 *
 *  Because docblock tags don't support Attributes, their equivalents are just parsed as strings.
 *  Core attributes are available by inserting strings that look like them on lines preceding a var tag. The
 *  actual Attribute classes need not be included, because this system just looks for strings that
 *  look like `#[CategoryName("category_name")]` or `[ConfigValue("config_key")]`.
 */
function ctx_include(string $file): mixed
{
    return Context::get(Invoker::class)->include($file);
}