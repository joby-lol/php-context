# Context Injection - Advanced Documentation

This document provides detailed information about the internal components and advanced features of the Context Injection
library.

## Table of Contents

- [The Context Class](#the-context-class)
- [The Container Class](#the-container-class)
- [Attributes](#attributes)
    - [ConfigValue Attribute](#configvalue-attribute)
    - [CategoryName Attribute](#categoryname-attribute)
- [PathGuard services](#pathguard-services)
    - [DefaultIncludeGuard Implementation](#defaultincludeguard-implementation)
- [Configuration System](#configuration-system)
    - [Config Interface](#config-interface)
    - [DefaultConfig Implementation](#defaultconfig-implementation)
    - [ConfigValue Types](#configvalue-types)
  - [Advanced Configuration Features](#advanced-configuration-features)

## The Context Class

The `Context` class is the main static entry point for the Context Injection system. It provides static methods for
retrieving objects, registering classes/objects, and checking if a class is registered.

```php
namespace Joby\ContextInjection;

class Context
{
    // Get an object from the container
    public static function get(string $class, string $category = 'default'): mixed;

    // Get or set the container instance
    public static function container(Container|null $container = null): Container;

    // Register a class or object with the container
    public static function register(string|object $class, string $category = "default"): void;

    // Check if a class is registered in the container
    public static function isRegistered(string $class): bool;
}
```

The `Context` class is designed to be used in a static context, so it can be accessed from anywhere in your codebase
without needing to pass it around. It acts as a facade for the underlying `Container` instance.

Key features:

- Not marked as `final`, so you can extend it to create your own context injector
- By default child classes will share their container with the parent Context class, but this can be overridden by
  changing their CONTEXT_CLASS constant
- Provides a simple static API for the most common operations

## The Container Class

The `Container` class is responsible for managing registered classes and objects. It provides methods for registering
classes/objects, retrieving objects, and checking if a class is registered.

```php
namespace Joby\ContextInjection;

class Container
{
    // Constructor with optional config and invoker
    public function __construct(Config|null $config = null, Invoker|null $invoker = null);

    // Register a class or object
    public function register(string|object $class, string $category = 'default'): void;

    // Get an object by class name and category
    public function get(string $class, string $category = 'default'): object;

    // Check if a class is registered
    public function isRegistered(string $class, string $category = 'default'): bool;
}
```

Key features:

- Manages registered classes and objects by category
- Handles instantiation of objects when needed
- Detects and prevents circular dependencies
- Automatically registers parent classes and interfaces
- Lazy-loads objects only when they're requested

### How the Container Works

1. When you register a class or object, the container:
    - Stores the class name in the `$classes` array
    - If an object is provided, stores it in the `$built` array
    - Registers all parent classes and interfaces as aliases

2. When you request an object, the container:
    - Checks if the object is already built and returns it if available
    - Otherwise, instantiates the object using the `Invoker`
    - Stores the built object for future requests
    - Returns the object

3. The container uses the `Invoker` to instantiate objects, which:
    - Analyzes the constructor parameters
    - Resolves dependencies from the container
    - Creates the object with the resolved dependencies

## Attributes

The Context Injection library uses PHP 8 attributes to provide additional information about how dependencies should be
resolved.

### ConfigValue Attribute

The `ConfigValue` attribute is used to inject configuration values into function parameters.

Usage example:

```php
function generateReport(
    #[ConfigValue('app.name')] string $appName,
    #[ConfigValue('db.host')] string $dbHost
) {
    echo "Generating report for $appName using database at $dbHost";
}

// Execute with config values automatically injected
ctx_execute('generateReport');
```

### CategoryName Attribute

The `CategoryName` attribute is used to specify the category from which a parameter should be pulled when injecting
dependencies.

Usage example:

```php
function processUser(
    #[CategoryName('current')] User $user,
    Logger $logger
) {
    $logger->log("Processing user: {$user->getName()}");
    // ...
}

// Register a user in the 'current' category
$currentUser = new User(1);
ctx_register($currentUser, 'current');

// Execute with the current user automatically injected
ctx_execute('processUser');
```

## PathGuard services

The Context Injection library provides a `PathGuard` interface that can be used to check whether a given file is
allowed to be used for various purposes. By default, this is used in the `Invoker` under the name `IncludeGuard` as a
mechanism for preventing the inclusion of untrusted files. The interface is a single `check($filename)` method that
returns a boolean indicating whether the file is allowed to be used. You can implement your own `PathGuard` services
to customize the behavior, or you can use the `DefaultReadGuard`, `DefaultWriteGuard` and `DefaultIncludeGuard`
that provide basic management by allowing/denying directories and full path names.

The `PathGuard` interface is intended as a general-purpose mechanism for preventing untrusted code from being
included, and may also be used by other services if they need to check whether a file is allowed to be
included/executed.

### DefaultIncludeGuard Implementation

Default include guard implementation. Does basic checks to allow and deny includes by directory or full directory. File
rules take precedence over directory rules, and after that deny rules take precedence over allow rules. This means that
you can allow a directory, but deny files or subdirectories within it. It also means that you can deny a directory, but
allow specific files within it.

```php
// Instantiate a new default include guard and register it
$guard = new Joby\ContextInjection\PathGuard\DefaultIncludeGuard();
ctx_register($guard);
// Allow a directory
$guard->allowDirectory('/path/to/allow');
// Deny a directory (takes precedence over allow rules)
$guard->denyDirectory('/path/to/allow/deny');
// Allow a file (takes precedence over any directory-level rules)
$guard->allowFile('/path/to/allow/deny/file.php');
// Deny a file (takes precedence over any directory-level rules)
$guard->denyFile('/path/to/allow/file.php');
```

## Configuration System

The Context Injection library includes a powerful configuration system that allows you to manage configuration values
and inject them as dependencies.

### Config Interface

The `Config` interface defines methods for managing configuration values.

```php
namespace Joby\ContextInjection\Config;

interface Config
{
    // Check if a config key exists
    public function has(string $key): bool;

    // Get a config value
    public function get(string $key): mixed;

    // Set a config value
    public function set(string $key, mixed $value): void;

    // Unset a config value
    public function unset(string $key): void;

    // Interpolate a string with config values
    public function interpolate(string $value): string;
}
```

### DefaultConfig Implementation

The `DefaultConfig` class is the default implementation of the `Config` interface. It provides a comprehensive
configuration system with features like default values, value locators, and caching.

```php
namespace Joby\ContextInjection\Config;

class DefaultConfig implements Config
{
    public function __construct(
        array $defaults = [],
        array $values = [],
        array $global_locators = [],
        array $prefix_locators = [],
    );

    // Config interface methods
    public function has(string $key): bool;
    public function get(string $key): mixed;
    public function set(string $key, mixed $value): void;
    public function unset(string $key): void;
    public function interpolate(string $value): string;
}
```

Key features:

- Default values: Fallback values for when a key isn't explicitly set
- Explicitly set values: Values set directly using the `set` method
- Value locators: Callbacks that can dynamically locate configuration values
    - Global locators: Run on any key
    - Prefix locators: Run only on keys with a specific prefix
- Value caching: Caches values to improve performance

### ConfigValue Types

The configuration system supports several types of complex values through the `ConfigValue` interface.

#### InterpolatedValue

The `InterpolatedValue` class is used to interpolate strings with configuration values.

Usage example:

```php
$config->set('app.name', 'My App');
$config->set('welcome.message', new InterpolatedValue('Welcome to ${app.name}!'));

echo $config->get('welcome.message'); // Outputs: "Welcome to My App!"
```

#### LazyValue

The `LazyValue` class is used to lazily evaluate configuration values using a callback function.

Usage example:

```php
$config->set('current.time', new LazyValue(function() {
    return date('Y-m-d H:i:s');
}));

echo $config->get('current.time'); // Outputs the current time when accessed
```

#### NullValue

The `NullValue` class is used to explicitly represent null values in the configuration system.

Usage example:

```php
// Use NullValue when you want to explicitly set a config value to null
$config->set('optional.setting', new NullValue());
```

### Advanced Configuration Features

The configuration system supports several advanced features:

#### Type Validation

When using the `ConfigValue` attribute, the system validates that the configuration value matches the expected type:

```php
function example(
    #[ConfigValue('string.key')] string $stringParam,
    #[ConfigValue('int.key')] int $intParam,
    #[ConfigValue('bool.key')] bool $boolParam,
    #[ConfigValue('array.key')] array $arrayParam
) {
    // All parameters are guaranteed to be of the correct type
}
```

#### Optional Parameters

You can make configuration parameters optional by providing default values:

```php
function example(
    #[ConfigValue('optional.key')] string $param = 'default value'
) {
    // $param will be 'default value' if 'optional.key' doesn't exist
}
```

#### Nullable Parameters

You can allow configuration values to be null by using nullable types:

```php
function example(
    #[ConfigValue('nullable.key')] ?string $param
) {
    // $param can be null if 'nullable.key' is null
}
```

#### Union Types

You can use union types to allow multiple types for a configuration value:

```php
function example(
    #[ConfigValue('union.key')] string|int $param
) {
    // $param can be either a string or an integer
}
```

#### String Interpolation

You can interpolate strings with configuration values using the `interpolate` method:

```php
$config->set('app.name', 'My App');
$config->set('db.host', 'localhost');

$connectionString = $config->interpolate('Connecting to ${db.host} for ${app.name}');
// Result: "Connecting to localhost for My App"
```
