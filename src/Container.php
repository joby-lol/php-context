<?php /** @noinspection ALL */

namespace Joby\ContextInjection;

use Joby\ContextInjection\Invoker\DefaultInvoker;
use Joby\ContextInjection\Invoker\Invoker;
use RuntimeException;

class Container
{
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

    public function __construct(bool $register_defaults = true)
    {
        if ($register_defaults) $this->registerDefaults();
    }

    /**
     * Register default built-in classes needed for the Context to function.
     * These may be overridden by user-defined classes, but running this method
     * will make all the necessary basic dependencies available for use, and
     * get the context ready for use.
     */
    public function registerDefaults(): void
    {
        $this->register(DefaultInvoker::class);
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
     * Get all the classes and interfaces that a given class inherits from or
     * implements, including itself. This is used to ensure that all classes
     * are retreivable even if they extend the class that is being requested.
     *
     * @param class-string $class
     * @return array<class-string>
     */
    protected function allClasses(string $class): array
    {
        return array_merge(
            [$class], // start with the class itself
            class_parents($class) ?: [], // add all parent classes
            class_implements($class) ?: [] // add all interfaces implemented by the class
        );
    }

    /**
     * Get an object of the given class, either by retrieving a built copy of it
     * or by instantiating it for the first time if necessary.
     *
     * @template T of object
     * @param class-string<T> $class the class of object to retrieve
     * @param string $category the category of the object, if applicable (i.e. "current" to get the current page for a request, etc.)
     * @return T
     */
    public function get(string $class, string $category = 'default'): object
    {
        $output = $this->getBuilt($class, $category)
            ?? $this->instantiate($class, $category);
        // otherwise return the output
        assert($output instanceof $class);
        return $output;
    }

    /**
     * Get the built copy of the given class if it exists.
     *
     * @template T of object
     * @param class-string<T> $class the class of object to retrieve
     * @param string $category the category of the object, if applicable (i.e. "current" to get the current page for a request, etc.)
     * @return T|null
     */
    protected function getBuilt(string $class, string $category): object|null
    {
        // if the class is not registered, return null
        if (!$this->isRegistered($class, $category)) {
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
     * Check if a class is registered in the context under the given category,
     * without instantiating it. This is useful for checking if a class is
     * available without the overhead of instantiation.
     * @param class-string $class
     */
    public function isRegistered(
        string $class,
        string $category = 'default',
    ): bool
    {
        // check if the class is registered in the given category
        return isset($this->classes[$category][$class]);
    }

    /**
     * Instantiate the given class if it has not been instantiated yet. Returns
     * the built object when finished. Returns null if the given class is not
     * registered under the given category.
     *
     * @template T of object
     * @param class-string<T> $class the class of object to instantiate
     * @param string $category the category of the object, if applicable (i.e. "current" to get the current page for a request, etc.)
     * @return T
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
        // if the class is the Invoker it needs to be instantiated directly
        if (is_a($actual_class, Invoker::class, true)) {
            $built = new $actual_class($this);
        } else {
            $built = $this->get(Invoker::class)->instantiate($actual_class);
        }
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