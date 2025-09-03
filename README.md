# Context Injection

A lightweight, static dependency injection container for PHP that combines simplicity with powerful features.

## What is it?

Context Injection is a PHP library that provides a global static dependency injection container. It allows you to:

- Register and retrieve services/objects from anywhere in your codebase
- Automatically resolve dependencies when instantiating objects
- Execute callables with automatically injected dependencies
- Include files with docblock-based dependency injection

## Who is it for?

This library is ideal for:

- Developers who want a simple, no-configuration dependency injection solution
- Projects where passing a container through every layer is impractical
- Applications that need a balance between the simplicity of global access and the power of dependency injection
- Developers who appreciate the convenience of global functions for common operations

## Installation

```bash
composer require joby/context
```

## Usage Examples

The easiest way to use this library is with a handful of global functions that are registered via Composer.
These will generally cover most use cases.

### Registering Objects and Classes

```php
// Register a class (lazy-loaded)
ctx_register(UserService::class);

// Register an object instance
$logger = new Logger();
ctx_register($logger);

// Register with a specific category
$currentUser = new User(1);
ctx_register($currentUser, 'current');
```

### Retrieving Objects

```php
// Get a service from the container
$userService = ctx(UserService::class);

// Get a service with a specific category
$currentUser = ctx(User::class, 'current');
```

### Executing Callables with Dependency Injection

```php
// Define a function with type-hinted dependencies
function processUser(UserService $userService, Logger $logger) {
    $logger->log('Processing user...');
    return $userService->process();
}

// Execute the function with automatically resolved dependencies
$result = ctx_execute('processUser');
```

### Including Files with Dependency Injection

First create a file with docblock dependencies:

```php
/**
 * @var UserService $userService
 * @var Logger $logger
 */

// these services all magically exist when the file is included via ctx_include()
$logger->log('Generating user report');
return $userService->generateReport();
```

Then you can include that file, and the docblock will be parsed to inject dependencies.

```php
// Include the file with dependencies automatically injected
$report = ctx_include('/path/to/user_report.php');
```

#### Security note for including files

Depending on your project's security requirements, you may want to have checks in place to ensure that included files
are coming from trusted locations. In particular, if their paths are derived from user input, you should establish some
kind of checks. Includes can be automatically checked for malicious paths by registering an object that implements the
interface `Joby\ContextInjection\IncludeGuard\IncludeGuard`.

This interface is designed to be very simple, so that it can be easily implemented, or used by other services that might
be capable of including or otherwise executing files.

There is a basic implementation of this interface in the library already, at
`Joby\ContextInjection\IncludeGuard\DefaultIncludeGuard`. It is a simple allow/deny list that lets you allow/deny
specific directories and/or individual files in one easily-configurable place.

## Built-in Configuration System

The library includes a configuration system that allows you to inject config values as dependencies:

```php
// Set configuration values
$config = ctx(Config::class);
$config->set('app.name', 'My Application');
$config->set('db.host', 'localhost');

// Use config values as dependencies in functions
function generateReport(
    #[ConfigValue('app.name')] string $appName,
    #[ConfigValue('db.host')] string $dbHost
) {
    echo "Generating report for $appName using database at $dbHost";
}

// Execute with config values automatically injected
ctx_execute('generateReport');
```

You can also use config values in included files:

```php
// file: report.php
/**
 * #[ConfigValue('app.name')]
 * @var string $appName
 * 
 * #[ConfigValue('db.host')]
 * @var string $dbHost
 */

echo "Generating report for $appName using database at $dbHost";

// Include with config values automatically injected
ctx_include('report.php');
```

The configuration system supports:

- Type validation (string, int, bool, array, etc.)
- Optional parameters with default values
- Nullable parameters
- Union types
- String interpolation with `$config->interpolate("Value from ${config.key}")`

## Advanced Documentation

For more detailed information about the internal components and advanced features of the Context Injection library,
please see the [Advanced Documentation](README_advanced.md).

## License

This project is licensed under the [MIT License](LICENSE).
