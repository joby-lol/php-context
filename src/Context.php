<?php

namespace Joby\ContextInjection;

/**
 * The main static context injector. This works similarly to a normal dependency
 * injection container, but it is static and does not require any instantiation
 * or configuration. It is designed to be used in a static context, so that it
 * can be used anywhere in the codebase without needing to pass it around.
 *
 * Note that this class is *not* final, so you can extend it to create your own
 * context injector and add features that are useful for your application. It is
 * also noteworthy that child classes will have their own lists of registered
 * classes and built objects, so they will not interfere with each other or the
 * base class.
 */
class Context
{
    const CONTEXT_CLASS = Context::class;
    /**
     * @var array<string,Container>
     */
    protected static array $containers = [];

    /**
     * Get an object of the given class, either by retrieving a built copy of it
     * or by instantiating it for the first time if necessary.
     *
     * @template T of object
     * @param class-string<T> $class the class of object to retrieve
     * @param string $category the category of the object, if applicable (i.e. "current" to get the current page for a request, etc.)
     * @return T
     */
    public static function get(string $class, string $category = 'default'): mixed
    {
        return static::container()->get($class, $category);
    }

    public static function container(Container|null $container = null): Container
    {
        return static::$containers[static::CONTEXT_CLASS] ??= ($container ?? new Container());
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
     * @param class-string|object $class the class name or object to register
     * @param string $category the category of the class, if applicable (i.e. "current" to get the current page for a request, etc.)
     */
    public static function register(string $class, string $category = "default"): void
    {
        static::container()->register($class, $category);
    }

    /**
     * Check if a class is registered in the context under the given category,
     * without instantiating it. This is useful for checking if a class is
     * available without the overhead of instantiation.
     * @param class-string $class
     */
    public static function isRegistered(string $class): bool
    {
        return static::container()->isRegistered($class);
    }
}
