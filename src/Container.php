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

namespace Joby\ContextInjection;

use Joby\ContextInjection\Cache\Cache;
use Joby\ContextInjection\Cache\DefaultCache;
use Joby\ContextInjection\Config\Config;
use Joby\ContextInjection\Config\DefaultConfig;
use Joby\ContextInjection\Invoker\DefaultInvoker;
use Joby\ContextInjection\Invoker\Invoker;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use RuntimeException;

/**
 * A container implementation that provides dependency injection and object management functionalities. This container
 * allows registrations of classes or objects, instantiation of classes on demand, and management of object
 * dependencies, optionally split across multiple named categories.
 */
class Container implements ContainerInterface
{
    public readonly Cache $cache;
    public readonly Config $config;
    public readonly Invoker $invoker;
    /**
     * Array holding the classes that have been registered, including their
     * parent classes, sorted first by category and then by class name, listing
     * the class names as strings.
     *
     * The listed class names are then used to look up or instantiate objects
     * as needed.
     *
     * @var array<string, array<class-string, class-string>>
     */
    protected array $classes = [];
    /**
     * Array holding the built objects, indexed first by category and then by
     * class name. There will be multiple copies of most objects, as they are
     * saved under all parent classes as well.
     *
     * @var array<string, array<class-string, object>>
     */
    protected array $built = [];
    /**
     * List of the current dependencies that are being instantiated to detect circular dependencies.
     *
     * @var array<string, true>
     */
    protected array $instantiating = [];

    public function __construct(Config|null $config = null, Invoker|null $invoker_class = null, Cache|null $cache = null)
    {
        $this->cache = $cache ?? new DefaultCache();
        $this->config = $config ?? new DefaultConfig();
        $this->invoker = $invoker_class ? new $invoker_class($this) : new DefaultInvoker($this);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function __clone()
    {
        $this->config = clone $this->config;
        $unique_objects = [];
        foreach ($this->built as $category => $built) {
            foreach ($built as $object) {
                if (!array_key_exists(spl_object_id($object), $unique_objects)) $unique_objects[spl_object_id($object)] = [clone $object, []];
                $unique_objects[spl_object_id($object)][1][] = $category;
            }
        }
        $this->built = [];
        foreach ($unique_objects as $obj) {
            foreach ($obj[1] as $category) {
                $this->register($obj[0], $category);
            }
        }
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
     * @throws InvalidArgumentException
     */
    public function register(
        string|object $class,
        string        $category = 'default',
    ): void
    {
        // if the class is an object, get its class name
        if (is_object($class)) {
            $object = $class;
            $class = get_class($class);
            assert(class_exists($class), "The class $class does not exist.");
        }
        // get all parent classes of the registered class
        $all_classes = $this->allClasses($class);
        // save all classes under the class name alias list
        foreach ($all_classes as $alias_class) {
            $this->classes[$category][$alias_class] = $class;
        }
        // if there is an object, also save it under the built objects list
        if (isset($object)) {
            foreach ($all_classes as $alias_class) {
                $this->built[$category][$alias_class] = $object;
            }
        }
    }

    /**
     * Get an object of the given class, either by retrieving a built copy of it
     * or by instantiating it for the first time if necessary.
     *
     * @template T of object
     * @param class-string<T> $id       the class of object to retrieve
     * @param string          $category the category of the object, if applicable (i.e. "current" to get the current
     *                                  page for a request, etc.)
     *
     * @return T
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function get(string $id, string $category = 'default'): object
    {
        // short-circuit on built-in classes
        if ($category === 'default') {
            if ($id === Invoker::class) return $this->invoker;
            if ($id === Cache::class) return $this->cache;
            if ($id === Config::class) return $this->config;
        }
        // normal get/instantiate
        $output = $this->getBuilt($id, $category)
            ?? $this->instantiate($id, $category);
        // otherwise return the output
        assert($output instanceof $id);
        return $output;
    }

    /**
     * Check if a class is registered in the context under the given category,
     * without instantiating it. This is useful for checking if a class is
     * available without the overhead of instantiation.
     *
     * @param class-string $id
     */
    public function has(
        string $id,
        string $category = 'default',
    ): bool
    {
        // short-circuit on built-in classes
        if ($id === Invoker::class) return true;
        if ($id === Cache::class) return true;
        if ($id === Config::class) return true;
        // check if the class is registered in the given category
        return isset($this->classes[$category][$id]);
    }

    /**
     * Get all the classes and interfaces that a given class inherits from or
     * implements, including itself. This is used to ensure that all classes
     * are retrievable even if they extend the class that is being requested.
     *
     * @param class-string $class
     *
     * @return array<class-string>
     * @throws InvalidArgumentException
     */
    protected function allClasses(string $class): array
    {
        return $this->cache->cache(
            key: 'container/allClasses/' . md5($class),
            callback: function () use ($class) {
                return array_merge(
                    [$class],                    // start with the class itself
                    class_parents($class) ?: [], // add all parent classes
                    class_implements($class) ?: [] // add all interfaces implemented by the class
                );
            }
        );
    }

    /**
     * Get the built copy of the given class if it exists.
     *
     * @template T of object
     * @param class-string<T> $class    the class of object to retrieve
     * @param string          $category the category of the object, if applicable (i.e. "current" to get the current
     *                                  page for a request, etc.)
     *
     * @return T|null
     */
    protected function getBuilt(string $class, string $category): object|null
    {
        // if the class is not registered, return null
        if (!$this->has($class, $category)) {
            return null;
        }
        // return null if the built object does not exist
        if (!isset($this->built[$category][$class])) {
            return null;
        }
        // return the built object
        assert(
            $this->built[$category][$class] instanceof $class,
            sprintf(
                "The built object for class %s in category %s is not of the expected type (got a %s).",
                $class,
                $category,
                get_class($this->built[$category][$class])
            )
        );
        return $this->built[$category][$class];
    }

    /**
     * Instantiate the given class if it has not been instantiated yet. Returns
     * the built object when finished. Returns null if the given class is not
     * registered under the given category.
     *
     * @template T of object
     * @param class-string<T> $class    the class of object to instantiate
     * @param string          $category the category of the object, if applicable (i.e. "current" to get the current
     *                                  page for a request, etc.)
     *
     * @return T
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    protected function instantiate(string $class, string $category): object
    {
        // if the class is not registered, return null
        if (!isset($this->classes[$category][$class])) {
            throw new RuntimeException(
                "The class $class is not registered in the context under category $category. " .
                "Did you forget to call " . get_called_class() . "::register() to register it?"
            );
        }
        // get the actual class name from the registered classes
        $actual_class = $this->classes[$category][$class];
        // check for circular dependencies
        $dependency_key = implode('|', [$category, $actual_class]);
        if (isset($this->instantiating[$dependency_key])) {
            throw new RuntimeException(
                "Circular dependency detected when instantiating $class in category $category. " .
                implode(' -> ', array_keys($this->instantiating))
            );
        }
        // Mark this class as currently being instantiated
        $this->instantiating[$dependency_key] = true;
        // instantiate the class and save it under the built objects
        $built = $this->get(Invoker::class)->instantiate($actual_class);
        // save the built object under all parent classes and interfaces
        $all_classes = $this->allClasses($built::class);
        foreach ($all_classes as $alias_class) {
            $this->built[$category][$alias_class] = $built;
        }
        // clean up list of what is currently instantiating
        unset($this->instantiating[$dependency_key]);
        // return the output
        assert($built instanceof $class);
        return $built;
    }
}