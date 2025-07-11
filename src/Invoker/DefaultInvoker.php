<?php

namespace Joby\ContextInjection\Invoker;

use Closure;
use InvalidArgumentException;
use Joby\ContextInjection\Config\ConfigTypeException;
use Joby\ContextInjection\Container;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionUnionType;
use RuntimeException;

/**
 * Utility class for reflection and invocation of classes and functions, used to
 * execute functions and instantiate classes with the correct parameters.
 */
class DefaultInvoker implements Invoker
{
    public function __construct(protected Container $container)
    {
    }

    /**
     * Instantiate a class of the given type, resolving all its dependencies
     * using the context injection system.
     *
     * @template T of object
     * @param class-string<T> $class
     *
     * @return T
     * @throws ReflectionException
     * @noinspection PhpDocSignatureInspection
     */
    public function instantiate(string $class): object
    {
        if (!method_exists($class, '__construct')) $object = new $class;
        else $object = new $class(...$this->buildFunctionArguments([$class, '__construct']));
        assert($object instanceof $class, "The instantiated object is not of type $class.");
        return $object;
    }

    /**
     * Include a given file, parsing for an opening docblock and resolving var tags as if they
     * were dependencies to be loaded from the container.
     *
     * Because docblock tags don't support Attributes, their equivalents are just parsed as strings.
     * Core attributes are available by inserting strings that look like them on lines preceding a var tag. The
     * actual Attribute classes need not be included, because this system just looks for strings that
     * look like `#[CategoryName("category_name")]` or `[ConfigValue("config_key")]`.
     */
    public function include(string $file): mixed
    {
        $key = md5_file($file);
        $vars = $this->cache(
            "include/$key",
            function () use ($file): mixed {
                $content = file_get_contents($file);
                if ($content === false) throw new RuntimeException("Could not read file $file.");
                $vars = [];
                // parse the first docblock at the start of the file
                if (preg_match('/^\s*(?:<\?php\s+)?\/\*\*(.*?)\*\//s', $content, $matches)) {
                    // parse the docblock itself
                    $docblock = $matches[1];
                    $lines = preg_split('/\r?\n/', $docblock);
                    $lines = array_map(function ($line) {
                        return trim(preg_replace('/^\s*\*\s*/', '', $line));
                    }, $lines);
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
                            // check if objects are a fully qualified class name
                            if (!in_array($type, ['int', 'string', 'float', 'bool', 'array', 'false'])) {
                                // this is a non-scalar type
                                $type = ltrim($type, '\\');
                                if (!class_exists($type)) {
                                    // search the whole file for a use statement ending with this class name
                                    $pattern1 = '/use\s+([^;]+\\\\' . preg_quote($type) . ')\s*;/m';
                                    $pattern2 = '/use\s+([^\s]+)\s+as\s+' . preg_quote($type) . '\s*;/m';
                                    if (preg_match($pattern1, $content, $m)) {
                                        $type = $m[1];
                                    } elseif (preg_match($pattern2, $content, $m)) {
                                        $type = $m[1];
                                    } else {
                                        throw new RuntimeException("Could not find use statement for class $type.");
                                    }
                                }
                            }
                            // build value
                            $varName = $matches[2];
                            if ($currentConfigKey) {
                                // this variable is a config value
                                // TODO: handle union types for config
                                $vars[$varName] = new ConfigPlaceholder(
                                    $currentConfigKey,
                                    [$type],
                                    false,
                                    null,
                                    $allowNull
                                );
                            } else {
                                // this variable is an object
                                $vars[$varName] = new ObjectPlaceholder($type, $currentCategory ?? 'default');
                            }
                        }
                    }
                }
                return $vars;
            }
        );
        // extract variables into scope, include the file and return its output
        return include_isolated($file, $this->resolvePlaceholders($vars));
    }

    /**
     * @template T of object
     * @param callable(mixed...):T $fn
     *
     * @return T|object
     * @throws ReflectionException
     */
    public
    function execute(callable $fn): mixed
    {
        assert(is_string($fn) || $fn instanceof Closure, 'The provided callable must be a string or a Closure.');
        $reflection = new ReflectionFunction($fn);
        // call with built arguments and return result
        // @phpstan-ignore-next-line this will always return the return type of the passed callable
        return $reflection->invokeArgs($this->buildFunctionArguments($fn));
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
     * @return array
     * @throws ReflectionException
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
            $args = $this->cache(
                "buildFunctionArguments/$cache_key",
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
     * @return ConfigPlaceholder[]|ObjectPlaceholder[]
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
            assert(class_exists($type), "The type \"$type\" does not exist.");
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
                );
                continue;
            }
            // get value and add it to the args list
            $args[] = new ObjectPlaceholder(
                $type,
                $category
            );
        }
        return $args;
    }

    protected function resolvePlaceholders(array $args): array
    {
        return array_map(
            function (ConfigPlaceholder|ObjectPlaceholder $arg): mixed {
                if ($arg instanceof ConfigPlaceholder) {
                    // attempt to get config value
                    if (!$this->container->config->has($arg->key)) {
                        if (!$arg->is_optional) throw new RuntimeException("Config value for key {$arg->key} does not exist.");
                        $value = $arg->default;
                    } else {
                        $value = $this->container->config->get($arg->key);
                    }
                    // validate type
                    $this->validateConfigValueType($value, $arg->key, $arg->valid_types, $arg->allows_null);
                    return $value;
                } elseif ($arg instanceof ObjectPlaceholder) {
                    // arg must be an ObjectPlaceholder
                    return $this->container->get($arg->class, $arg->category);
                }
                throw new RuntimeException("Unknown placeholder type.");
            },
            $args
        );
    }

    /**
     * Validate that a config value is of the type expected by the parameter and throw an exception
     * if it is an invalid/unexpected type.
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
 * Includes a PHP file in an isolated scope with extracted variables.
 *
 * @param string              $path The path to the PHP file to be included.
 * @param array<string,mixed> $vars An associative array of variables to extract and make available in the included
 *                                  file's scope.
 *
 * @return mixed Returns the result of the included file. Typically, this is the return value of the script if
 *               specified, or 1 if the script has no return value.
 */
function include_isolated(string $path, array $vars): mixed
{
    extract($vars);
    return include $path;
}