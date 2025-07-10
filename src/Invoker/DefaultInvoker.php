<?php

namespace Joby\ContextInjection\Invoker;

use Closure;
use InvalidArgumentException;
use Joby\ContextInjection\Container;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

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
     * @param callable|array{class-string|object,string} $fn
     * @return array
     * @throws ReflectionException
     */
    protected function buildFunctionArguments(callable|array $fn): array
    {
        if (is_string($fn) || $fn instanceof Closure) {
            $reflection = new ReflectionFunction($fn);
        } elseif (is_array($fn)) {
            assert(is_string($fn[1]), 'The second element of the array must be a method name.');
            assert(is_object($fn[0]) || (is_string($fn[0]) && class_exists($fn[0])), 'The first element of the array must be a class name or an object.');
            $reflection = new ReflectionMethod($fn[0], $fn[1]);
        } else {
            throw new InvalidArgumentException('The provided callable is not a valid function or method.');
        }
        $parameters = $reflection->getParameters();
        $args = [];
        foreach ($parameters as $param) {
            // get the type hint of the parameter
            $type = (string)$param->getType();
            assert(!empty($type), "The parameter {$param->getName()} does not have a type hint.");
            assert(class_exists($type), "The type \"$type\" does not exist.");
            // hook for extending paramter resolution
            $hook = $this->resolveParameter($param);
            if (!is_null($hook)) {
                $args[] = $hook->value;
                continue;
            }
            // look for a ParameterValue attribute and use its value if it exists
            $attr = $param->getAttributes(ParameterValue::class);
            if (count($attr) > 0) {
                $args[] = $attr[0]->newInstance()->getValue();
                continue;
            }
            // if there is no ParameterValue attribute, we need to get the value
            // first look for a ParameterCategory attribute so we can determine the category
            $attr = $param->getAttributes(ParameterCategory::class);
            if (count($attr) > 0) {
                // if there is a ParameterCategory attribute, use its category
                $category = $attr[0]->newInstance()->category;
            } else {
                $category = 'default';
            }
            // get value and add it to the args list
            $args[] = $this->container->get($type, $category);
        }
        // return $args
        return $args;
    }

    /**
     * Resolve a parameter in your own way, if you want to easily extend this
     * class, this method can be overridden to provide custom parameter
     * resolution if you like. For example, you could add your own custom
     * attributes, or even invent some whole new way of resolving parameters
     * while still being able to fall back to the default style.
     *
     * If you return a ResolvedParameter, it will be used as the value for the
     * parameter. If you return null, the default resolution will be used.
     * @noinspection PhpUnusedParameterInspection
     */
    protected function resolveParameter(ReflectionParameter $param): ResolvedParameter|null
    {
        return null;
    }

    /**
     * @template T of object
     * @param callable(mixed...):T $fn
     * @return T|object
     * @throws ReflectionException
     */
    public function execute(callable $fn): mixed
    {
        assert(is_string($fn) || $fn instanceof Closure, 'The provided callable must be a string or a Closure.');
        $reflection = new ReflectionFunction($fn);
        // call with built arguments and return result
        // @phpstan-ignore-next-line this will always return the return type of the passed callable
        return $reflection->invokeArgs($this->buildFunctionArguments($fn));
    }
}
