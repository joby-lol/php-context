<?php

use Joby\ContextInjection\ContainerException;
use Joby\ContextInjection\Context;
use Joby\ContextInjection\Invoker\ExecutionException;
use Joby\ContextInjection\Invoker\InstantiationException;
use Joby\ContextInjection\Invoker\Invoker;
use Joby\ContextInjection\NotFoundException;
use Psr\SimpleCache\InvalidArgumentException;
use Joby\ContextInjection\Invoker\IncludeException;

/**
 * Retrieve a service/object from the context injection system.
 * Objects requested this way are lazy-loaded, meaning they are instantiated
 * only when they are first requested. This allows for better performance and
 * memory use.
 *
 * This function is an alias for `Context::get()`, available in the global
 * namespace for convenience and ease of use.
 *
 * @template T of object
 * @param class-string<T> $class    the class of object to retrieve
 * @param string          $category the category of the object, if applicable (i.e. "current" to get the current page
 *                                  for a request, etc.)
 *
 * @return object<T>
 *
 * @throws NotFoundException  No entry was found for **this** identifier
 * @throws ContainerException Error while retrieving the entry
 */
function ctx(string $class, string $category = 'default'): object
{
    return Context::get($class, $category);
}

/**
 * Build a new object of the given class. It will not be cached or stored anywhere else.
 *
 * @template T of object
 * @param class-string<T>|T $class
 *
 * @return object<T>
 *
 * @throws InstantiationException if an error occurs while instantiating the class
 */
function ctx_new(string|object $class): object
{
    if (is_object($class)) $class = get_class($class);
    return Context::new($class);
}

/**
 * Register a class or object to the context so that it can be retrieved
 * later using the get() method. This will also register all parent
 * classes and interfaces of the given class so that it can be retrieved
 * using any of them.
 *
 * If a class is given, it will be instantiated the first time it is
 * requested. If an object is given, it will be saved as a built object
 * and can be retrieved directly without instantiation.
 *
 * @param class-string|object $class    the class name or object to register
 * @param string              $category the category of the class, if applicable (i.e. "current" to get the current
 *                                      page for a request, etc.)
 *
 * @throws ContainerException
 */
function ctx_register(string|object $class, string $category = "default"): void
{
    Context::register($class, $category);
}

/**
 * Execute a callable, automatically instantiating any arguments it requires
 * from the context injection system. This allows for easy execution
 * of functions and methods with dependencies, without needing to manually
 * resolve anything.
 *
 * @template T of object
 * @param callable(mixed...):T $fn the callable to execute
 *
 * @return T
 *
 * @throws ExecutionException if an error occurs while executing the callable
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
 *
 *  This method will return either the output of the included file, or the value returned by it if there is one.
 *  Note that if the included script explicitly returns the integer "1" that cannot be differentiated from returning
 *  nothing at all. Generally the best practice is to return objects if you are returning anything, for unambiguous
 *  behavior. Although non-integer values are also a reasonable choice.
 *
 * @throws IncludeException if an error occurs while including the file
 */
function ctx_include(string $file): mixed
{
    return Context::get(Invoker::class)->include($file);
}