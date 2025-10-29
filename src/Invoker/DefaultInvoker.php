<?php
/*
 * Context Injection
 * https://github.com/joby-lol/php-context
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\ContextInjection\Invoker;

use Closure;
use InvalidArgumentException;
use Joby\ContextInjection\Config\Config;
use Joby\ContextInjection\Config\ConfigTypeException;
use Joby\ContextInjection\Container;
use Joby\ContextInjection\PathGuard\IncludeGuard;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionUnionType;
use RuntimeException;
use Throwable;

/**
 * Utility class for reflection and invocation of classes and functions, used to
 * execute functions and instantiate classes with the correct parameters.
 */
class DefaultInvoker implements Invoker
{
    protected IncludeGuard|null $include_guard;

    public function __construct(protected Container $container)
    {
    }

    /**
     * Instantiate a class of the given type, resolving all its dependencies
     *  using the context injection system.
     *
     * @template T of object
     * @param class-string<T> $class
     *
     * @return T
     *
     * @throws InstantiationException if an error occurs while instantiating the class
     */
    public function instantiate(string $class): object
    {
        try {
            if (!method_exists($class, '__construct')) $object = new $class;
            else $object = new $class(...$this->buildFunctionArguments([$class, '__construct']));
        } catch (Throwable $th) {
            throw new InstantiationException($class, $th);
        }
        assert($object instanceof $class, "The instantiated object is not of type $class.");
        return $object;
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
    public function include(string $file): mixed
    {
        try {
            // clean up path
            $path = realpath($file);
            if (!$path) throw new RuntimeException("File $file does not exist.");
            // check that file is readable
            if (!is_readable($path)) throw new RuntimeException("File $file is not readable.");
            // check that path is allowed to be included, if an IncludeGuard is registered
            if (false === $this->includeGuard()?->check($path)) throw new RuntimeException("File $file is not allowed to be included.");
            // cache further operations
            $key = md5_file($path);
            /** @var array<string,ConfigPlaceholder|ObjectPlaceholder> $vars */
            $vars = $this->cache(
                "include/vars/$key",
                /**
                 * @return array<string,ConfigPlaceholder|ObjectPlaceholder>
                 */
                function () use ($path): array {
                    $content = file_get_contents($path);
                    if ($content === false) throw new RuntimeException("Could not read file");
                    $vars = [];
                    $namespace = null;
                    // look for a namespace declaration
                    if (preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
                        $namespace = $matches[1];
                        $namespace = trim($namespace, '\\');
                    }
                    // parse the first docblock at the start of the file
                    if (preg_match('/\/\*\*(.*?)\*\//s', $content, $matches)) {
                        // parse the docblock itself
                        $docblock = $matches[1];
                        $lines = preg_split('/\r?\n/', $docblock) ?: [];
                        $lines = array_map(
                            function (string $line): string {
                                return trim(preg_replace('/^\s*\*\s*/', '', $line) ?? '');
                            },
                            $lines
                        );
                        $currentCategory = null;
                        $currentConfigKey = null;
                        foreach ($lines as $line) {
                            if (!$line) continue;
                            // check for category attribute with double quotes
                            if (preg_match('/#\[CategoryName\("([^"]+)"\)]/', $line, $matches)) {
                                $currentCategory = $matches[1];
                                continue;
                            }
                            // check for category attribute with single quotes
                            if (preg_match('/#\[CategoryName\(\'([^\']+)\'\)]/', $line, $matches)) {
                                $currentCategory = $matches[1];
                                continue;
                            }
                            // check for config value attribute with double quotes
                            if (preg_match('/#\[ConfigValue\("([^"]+)"\)]/', $line, $matches)) {
                                $currentConfigKey = $matches[1];
                                continue;
                            }
                            // check for config value attribute with single quotes
                            if (preg_match('/#\[ConfigValue\(\'([^\']+)\'\)]/', $line, $matches)) {
                                $currentConfigKey = $matches[1];
                                continue;
                            }
                            // parse @var declarations
                            if (preg_match('/@var\s+([^\s]+)\s+\$([^\s]+)/', $line, $matches)) {
                                $allowNull = false;
                                $type = $matches[1];
                                if (str_starts_with($type, '?')) {
                                    $allowNull = true;
                                    $type = substr($type, 1);
                                } elseif (str_starts_with($type, 'null|')) {
                                    $allowNull = true;
                                    $type = substr($type, 5);
                                } elseif (str_ends_with($type, '|null')) {
                                    $allowNull = true;
                                    $type = substr($type, 0, -5);
                                }
                                $types = explode('|', $type);
                                $types = array_map(
                                    /** @return class-string */
                                    function (string $type) use ($content, $namespace): string {
                                        // return scalar types unchanged
                                        if (in_array($type, ['int', 'string', 'float', 'bool', 'array', 'false'])) return $type;
                                        // make relative to namespace if the type doesn't start with a slash
                                        $relative = true;
                                        if (str_starts_with($type, '\\')) {
                                            $relative = false;
                                            $type = substr($type, 1);
                                        }
                                        // check if objects are a fully qualified class name
                                        if (!class_exists($type)) {
                                            // search the whole file for a use statement ending with this class name
                                            $pattern1 = '/use\s+([^;]+\\\\' . preg_quote($type) . ')\s*;/m';
                                            $pattern2 = '/use\s+([^\s]+)\s+as\s+' . preg_quote($type) . '\s*;/m';
                                            if (preg_match($pattern1, $content, $m)) {
                                                $type = $m[1];
                                                // $relative = false; // commented out because relative-ness no longer actually matters
                                            } elseif (preg_match($pattern2, $content, $m)) {
                                                $type = $m[1];
                                                // $relative = false; // commented out because relative-ness no longer actually matters
                                            } else {
                                                // if this is a relative class and there is a namespace, prepend the namespace
                                                if ($relative && $namespace) {
                                                    $class = $namespace . '\\' . $type;
                                                    if (class_exists($class)) {
                                                        return $class;
                                                    }
                                                }
                                                throw new RuntimeException("Could not find use statement for class $type.");
                                            }
                                        }
                                        // return parsed type
                                        return $type;
                                    },
                                    $types
                                );
                                // build value
                                sort($types);
                                $varName = $matches[2];
                                if ($currentConfigKey) {
                                    // this variable is a config value
                                    $vars[$varName] = new ConfigPlaceholder(
                                        $currentConfigKey,
                                        $types,
                                        false,
                                        null,
                                        $allowNull,
                                        $currentCategory ?? 'default'
                                    );
                                } else {
                                    // this variable is an object
                                    if (count($types) > 1) {
                                        throw new RuntimeException("Cannot use union types for objects.");
                                    }
                                    /** @var class-string $type */
                                    $type = reset($types);
                                    $vars[$varName] = new ObjectPlaceholder($type, $currentCategory ?? 'default');
                                }
                            }
                        }
                    }
                    return $vars;
                }
            );
            // extract variables into scope, include the file and return its output
            return include_isolated($path, $this->resolvePlaceholders($vars));
        } catch (IncludeException $e) {
            throw $e;
        } catch (Throwable $th) {
            throw new IncludeException($file, $th, '');
        }
    }

    /**
     * Execute a callable, automatically instantiating any arguments it requires from the context injection system.
     *  This allows for easy execution of functions and methods with dependencies, without needing to manually resolve
     *  anything.
     *
     * @template T of mixed
     * @param callable(mixed...):T $fn
     *
     * @return T
     *
     * @throws ExecutionException if an error occurs while executing the callable
     */
    public function execute(callable $fn): mixed
    {
        try {
            assert(is_string($fn) || $fn instanceof Closure, 'The provided callable must be a string or a Closure.');
            $reflection = new ReflectionFunction($fn);
            // call with built arguments and return result
            return $reflection->invokeArgs($this->buildFunctionArguments($fn));
        } catch (Throwable $th) {
            throw new ExecutionException($th);
        }
    }

    protected function includeGuard(): IncludeGuard|null
    {
        if (!isset($this->include_guard)) {
            $this->include_guard = null;
            if ($this->container->has(IncludeGuard::class)) {
                $this->include_guard = $this->container->get(IncludeGuard::class);
            }
        }
        return $this->include_guard;
    }

    /**
     * Helper method for caching the results of expensive operations.
     *
     * @param string   $key
     * @param callable $callback
     *
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function cache(string $key, callable $callback): mixed
    {
        return $this->container->cache->cache(
            'DefaultInvoker/' . $key,
            $callback
        );
    }

    /**
     * @param callable|array{class-string|object,string} $fn
     *
     * @return array<mixed>
     * @throws ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function buildFunctionArguments(callable|array $fn): array
    {
        if (is_string($fn)) {
            $reflection = new ReflectionFunction($fn);
            $cache_key = md5($fn);
        } elseif ($fn instanceof Closure) {
            $reflection = new ReflectionFunction($fn);
            $cache_key = null;
        } elseif (is_array($fn)) {
            assert(is_string($fn[1]), 'The second element of the array must be a method name.');
            assert(is_object($fn[0]) || (is_string($fn[0]) && class_exists($fn[0])), 'The first element of the array must be a class name or an object.');
            $reflection = new ReflectionMethod($fn[0], $fn[1]);
            $cache_key = md5(serialize([$fn[0], $fn[1]]));
        } else {
            throw new InvalidArgumentException('The provided callable is not a valid function or method.');
        }
        if ($cache_key) {
            /** @var array<ConfigPlaceholder|ObjectPlaceholder> $args */
            $args = $this->cache(
                "buildFunctionArguments/$cache_key",
                /** @return array<ConfigPlaceholder|ObjectPlaceholder> */
                function () use ($reflection): array {
                    return $this->doBuildFunctionArguments($reflection);
                }
            );
        } else {
            $args = $this->doBuildFunctionArguments($reflection);
        }
        // return $args
        return $this->resolvePlaceholders($args);
    }

    /**
     * @param ReflectionFunction|ReflectionMethod $reflection
     *
     * @return array<ConfigPlaceholder|ObjectPlaceholder>
     * @throws ReflectionException
     */
    protected function doBuildFunctionArguments(ReflectionFunction|ReflectionMethod $reflection): array
    {
        $parameters = $reflection->getParameters();
        /** @var array<ConfigPlaceholder|ObjectPlaceholder> $args */
        $args = [];
        foreach ($parameters as $param) {
            // get the type hint of the parameter
            $type = (string)$param->getType();
            assert(!empty($type), "The parameter {$param->getName()} does not have a type hint.");
            // if there is no ParameterValue attribute, we need to get the value
            // first look for a ParameterCategory attribute so we can determine the category
            $attr = $param->getAttributes(CategoryName::class);
            if (count($attr) > 0) {
                // if there is a ParameterCategory attribute, use its category
                $category = $attr[0]->newInstance()->category;
            } else {
                $category = 'default';
            }
            // look for a ConfigValue attribute and use it to get a value from Config if it exists
            $attr = $param->getAttributes(ConfigValue::class);
            if (count($attr) > 0) {
                $attr = $attr[0]->newInstance();
                $types = $param->getType() instanceof ReflectionUnionType
                    ? $param->getType()->getTypes()
                    : [$param->getType()];
                $types = array_map(fn($type) => (string)$type, $types);
                $args[] = new ConfigPlaceholder(
                    $attr->key,
                    $types,
                    $param->isOptional(),
                    $param->isOptional() ? $param->getDefaultValue() : null,
                    $param->allowsNull(),
                    $category
                );
                continue;
            }
            // get value and add it to the args list
            assert(class_exists($type), "The class $type does not exist for parameter {$param->getName()}.");
            $args[] = new ObjectPlaceholder(
                $type,
                $category
            );
        }
        return $args;
    }

    /**
     * @template TKey
     * @param array<TKey,ConfigPlaceholder|ObjectPlaceholder> $args
     * @return array<TKey,mixed>
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function resolvePlaceholders(array $args): array
    {
        return array_map(
            function (ConfigPlaceholder|ObjectPlaceholder $arg): mixed {
                if ($arg instanceof ConfigPlaceholder) {
                    // attempt to get config value
                    $config = $arg->category === 'default'
                        ? $this->container->config
                        : $this->container->get(Config::class, $arg->category);
                    if (!$config->has($arg->key)) {
                        if (!$arg->is_optional) throw new RuntimeException("Config value for key $arg->key does not exist.");
                        $value = $arg->default;
                    } else {
                        $value = $config->get($arg->key);
                    }
                    // validate type
                    $this->validateConfigValueType($value, $arg->key, $arg->valid_types, $arg->allows_null);
                    return $value;
                }
                // arg must be an ObjectPlaceholder
                return $this->container->get($arg->class, $arg->category);
            },
            $args
        );
    }

    /**
     * Validate that a config value is of the type expected by the parameter and throw an exception
     * if it is an invalid/unexpected type.
     * 
     * @param array<string> $types
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function validateConfigValueType(mixed $value, string $key, array $types, bool $allowNull): void
    {
        if (is_null($value) && $allowNull) return;
        sort($types);
        $cache_key = md5(serialize([$value, $types]));
        $valid = $this->cache(
            "validateConfigValueType/$cache_key",
            function () use ($types, $value): bool {
                foreach ($types as $type) {
                    $valid = match ($type) {
                        'int' => is_int($value),
                        'string' => is_string($value),
                        'float' => is_float($value),
                        'bool' => is_bool($value),
                        'array' => is_array($value),
                        'false' => $value === false,
                        default => $value instanceof $type
                    };
                    if ($valid) return true;
                }
                return false;
            }
        );
        if (!$valid) {
            $typeString = implode('|', $types);
            if ($allowNull) $typeString .= '|null';
            throw new ConfigTypeException(sprintf(
                'Config value from "%s" expected to be of type %s, got %s',
                $key,
                $typeString,
                get_debug_type($value)
            ));
        }
    }
}

/**
 * Includes a PHP file in an isolated scope with extracted variables. Note that if the included script explicitly
 * returns the integer "1" that cannot be differentiated from returning nothing at all. Generally the best practice is
 * to return objects if you are returning anything, for unambiguous behavior. Although non-integer values are also a
 * reasonable choice.
 *
 * @param string              $path The path to the PHP file to be included.
 * @param array<string,mixed> $vars An associative array of variables to extract and make available in the included
 *                                  file's scope.
 *
 * @return mixed|string Returns the result of the included file if it was not the integer value 1, otherwise returns
 *                      the results of output buffering during execution.
 *
 * @throws IncludeException If an error occurs during the execution of the included file.
 */
function include_isolated(string $path, array $vars): mixed
{
    ob_start();
    try {
        extract($vars);
        $return = include $path;
    } catch (Throwable $th) {
        $buffer = ob_get_contents() ?: 'Error: output buffering content unavailable.';
        ob_end_clean();
        throw new IncludeException($path, $th, $buffer);
    }
    $buffer = ob_get_contents();
    ob_end_clean();
    if ($return === 1) return $buffer;
    return $return;
}