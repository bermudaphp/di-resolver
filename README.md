# Parameter Resolver

[![Tests](https://github.com/bermudaphp/di-resolver/actions/workflows/tests.yml/badge.svg)](https://github.com/bermudaphp/di-resolver/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

**[🇷🇺 Русская версия](README.RU.md)**

A powerful and flexible parameter resolution library for PHP that automatically resolves function and method parameters from various sources including arrays, DI containers, and configuration.

## Features

- **Multiple Resolution Strategies**: Resolve parameters by name, position, type, or attributes
- **DI Container Integration**: Seamless integration with PSR-11 containers
- **Attribute Support**: Native PHP 8+ attributes (`#[Config]`, `#[Inject]`)
- **Union Types**: Full support for PHP 8 union types
- **Extensible Architecture**: Easy to add custom resolvers
- **Zero Dependencies**: Core functionality requires no external dependencies
- **High Performance**: Optimized resolver chain with early termination

## Installation

```bash
composer require bermudaphp/di-resolver
```

## Quick Start

```php
use Bermuda\ParameterResolver\ParameterResolver;
use Bermuda\ParameterResolver\ArrayResolver;
use Bermuda\ParameterResolver\DefaultValueResolver;

// Create resolver with basic resolvers
$resolver = new ParameterResolver([
    new ArrayResolver(),           // Resolve from provided array
    new DefaultValueResolver()     // Use default values
]);

// Define a function
function greetUser(string $name, string $greeting = 'Hello', ?int $age = null) {
    return "$greeting, $name" . ($age ? " (age: $age)" : "");
}

// Resolve parameters
$reflection = new ReflectionFunction('greetUser');
$parameters = $reflection->getParameters();

$resolvedParams = $resolver->resolve($parameters, [
    'name' => 'John',
    'age' => 25
]);

// Result: ['John', 'Hello', 25]
echo greetUser(...$resolvedParams); // "Hello, John (age: 25)"
```

## Core Resolvers

### ArrayResolver
Resolves parameters by name or position from provided array:

```php
$resolver = new ArrayResolver();

// By name
$params = ['username' => 'john', 'email' => 'john@example.com'];

// By position  
$params = [0 => 'john', 1 => 'john@example.com'];
```

### ArrayTypedResolver
Resolves parameters by object type scanning:

```php
$resolver = new ArrayTypedResolver();

$params = [
    'userService' => new UserService(),
    'logger' => new Logger()
];

function processUser(UserService $service, Logger $logger) {
    // Parameters resolved automatically by type
}
```

### ContainerResolver
Resolves from PSR-11 DI containers with attribute support:

```php
use Bermuda\DI\Attribute\Config;
use Bermuda\DI\Attribute\Inject;

$resolver = new ContainerResolver($container);

function createUser(
    #[Config('database.host')] string $dbHost,
    #[Inject('user.repository')] UserRepository $repo,
    UserService $service  // Resolved by type from container
) {
    // All parameters resolved automatically
}
```

### DefaultValueResolver
Uses parameter default values:

```php
function processData(array $data, bool $validate = true, ?string $format = null) {
    // $validate will be true, $format will be null if not provided
}
```

### NullableResolver
Resolves nullable parameters to null:

```php
function optionalService(?LoggerInterface $logger = null) {
    // $logger will be null if not resolvable
}
```

## DI Container Integration

### Full Integration Example

```php
use Bermuda\ParameterResolver\ParameterResolver;

// Create resolver with all standard resolvers
$resolver = ParameterResolver::createDefaults($container);

class UserController
{
    public function createUser(
        #[Config('app.name')] string $appName,
        #[Inject('user.validator')] UserValidator $validator,
        UserRepository $repository,           // From container by type
        array $userData,                      // From provided parameters
        bool $sendEmail = true,               // Default value
        ?LoggerInterface $logger = null       // Nullable fallback
    ) {
        // All parameters resolved automatically
        $user = new User($userData);
        
        if ($validator->validate($user)) {
            $repository->save($user);
            
            if ($sendEmail && $logger) {
                $logger->info("User created in $appName");
            }
        }
        
        return $user;
    }
}

// Usage
$method = new ReflectionMethod(UserController::class, 'createUser');
$resolvedParams = $resolver->resolve(
    $method->getParameters(),
    ['userData' => $_POST, 'sendEmail' => false]
);

$controller = new UserController();
$user = $controller->createUser(...$resolvedParams);
```

## Configuration Management

The `Config` attribute supports dot notation and various data sources:

```php
use Bermuda\DI\Attribute\Config;

// Simple config access
function connectDatabase(#[Config('database.host')] string $host) {}

// With default values
function getApiKey(#[Config('api.key', 'default-key')] string $key) {}

// Disable dot notation
function getLiteralKey(#[Config('app.debug', explodeDots: false)] bool $debug) {}

// Deep nesting
function getNestedValue(
    #[Config('services.external.api.endpoints.users')] string $endpoint
) {}
```

### ArrayAccess Support

```php
$config = new ArrayObject([
    'database' => ['host' => 'localhost', 'port' => 3306]
]);

$container->set('config', $config);

function connect(#[Config('database.host')] string $host) {
    // Works with ArrayAccess objects
}
```

## Advanced Usage

### Custom Resolvers

```php
class EnvironmentResolver implements ParameterResolverInterface
{
    public function resolve(
        ReflectionParameter $parameter,
        array $providedParameters = [],
        array $resolvedParameters = []
    ): ?array {
        $envVar = 'APP_' . strtoupper($parameter->getName());
        $value = $_ENV[$envVar] ?? null;
        
        return $value !== null ? [$parameter->getPosition(), $value] : null;
    }
}

// Add to resolver chain
$resolver = new ParameterResolver([
    new EnvironmentResolver(),
    new ArrayResolver(),
    new DefaultValueResolver()
]);
```

### Union Type Support

```php
function flexibleHandler(string|UserService $handler, int|float $value) {
    // Resolves first matching type from union
}

$params = [
    'UserService' => new UserService(),  // Matches UserService from union
    'value' => 42                        // Matches int from union
];
```

### Resolver Priorities

```php
$resolver = new ParameterResolver([
    new ArrayResolver(),           // Priority 1: Explicit parameters
    new ArrayTypedResolver(),      // Priority 2: Type matching
    new ContainerResolver($container), // Priority 3: DI container
    new DefaultValueResolver(),    // Priority 4: Default values
    new NullableResolver()         // Priority 5: Nullable fallback
]);
```

## Error Handling

```php
use Bermuda\ParameterResolver\ParameterResolutionException;

try {
    $resolvedParams = $resolver->resolve($parameters, $providedParams);
} catch (ParameterResolutionException $e) {
    echo "Failed to resolve parameter: " . $e->parameter->getName();
    echo "Provided parameters: " . print_r($e->providedParameters, true);
    echo "Already resolved: " . print_r($e->resolvedParameters, true);
}
```

## Performance Tips

1. **Order resolvers by frequency** - put most commonly used resolvers first
2. **Use typed parameters** when possible for faster ArrayTypedResolver matching
3. **Avoid deep nesting** in Config paths for better performance
4. **Cache reflection objects** when resolving the same signatures repeatedly

## Requirements

- PHP 8.4 or higher

## License

MIT License. See [LICENSE](LICENSE) for details.
