# Parameter Resolver

[![Tests](https://github.com/bermudaphp/di-resolver/actions/workflows/tests.yml/badge.svg)](https://github.com/bermudaphp/di-resolver/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

**[🇺🇸 English version](README.md)**

Мощная и гибкая библиотека для автоматического разрешения параметров функций и методов из различных источников: массивов, DI-контейнеров и конфигурации.

## Возможности

- **Множественные стратегии разрешения**: По имени, позиции, типу или атрибутам
- **Интеграция с DI**: Бесшовная интеграция с PSR-11 контейнерами
- **Поддержка атрибутов**: Нативные PHP 8+ атрибуты (`#[Config]`, `#[Inject]`)
- **Union типы**: Полная поддержка union типов PHP 8
- **Расширяемая архитектура**: Легко добавлять собственные резолверы
- **Без зависимостей**: Ядро не требует внешних зависимостей
- **Высокая производительность**: Оптимизированная цепочка с ранним завершением

## Установка

```bash
composer require bermudaphp/di-resolver
```

## Быстрый старт

```php
use Bermuda\ParameterResolver\ParameterResolver;
use Bermuda\ParameterResolver\ArrayResolver;
use Bermuda\ParameterResolver\DefaultValueResolver;

// Создание резолвера с базовыми резолверами
$resolver = new ParameterResolver([
    new ArrayResolver(),           // Разрешение из массива
    new DefaultValueResolver()     // Использование значений по умолчанию
]);

// Определяем функцию
function greetUser(string $name, string $greeting = 'Привет', ?int $age = null) {
    return "$greeting, $name" . ($age ? " (возраст: $age)" : "");
}

// Разрешение параметров
$reflection = new ReflectionFunction('greetUser');
$parameters = $reflection->getParameters();

$resolvedParams = $resolver->resolve($parameters, [
    'name' => 'Иван',
    'age' => 25
]);

// Результат: ['Иван', 'Привет', 25]
echo greetUser(...$resolvedParams); // "Привет, Иван (возраст: 25)"
```

## Основные резолверы

### ArrayResolver
Разрешает параметры по имени или позиции из переданного массива:

```php
$resolver = new ArrayResolver();

// По имени
$params = ['username' => 'ivan', 'email' => 'ivan@example.com'];

// По позиции
$params = [0 => 'ivan', 1 => 'ivan@example.com'];
```

### ArrayTypedResolver
Разрешает параметры по типу объекта, сканируя массив:

```php
$resolver = new ArrayTypedResolver();

$params = [
    'userService' => new UserService(),
    'logger' => new Logger()
];

function processUser(UserService $service, Logger $logger) {
    // Параметры разрешаются автоматически по типу
}
```

### ContainerResolver
Разрешение из DI-контейнеров PSR-11 с поддержкой атрибутов:

```php
use Bermuda\DI\Attribute\Config;
use Bermuda\DI\Attribute\Inject;

$resolver = new ContainerResolver($container);

function createUser(
    #[Config('database.host')] string $dbHost,
    #[Inject('user.repository')] UserRepository $repo,
    UserService $service  // Разрешается по типу из контейнера
) {
    // Все параметры разрешаются автоматически
}
```

### DefaultValueResolver
Использует значения параметров по умолчанию:

```php
function processData(array $data, bool $validate = true, ?string $format = null) {
    // $validate будет true, $format будет null, если не предоставлены
}
```

### NullableResolver
Разрешает nullable параметры в null:

```php
function optionalService(?LoggerInterface $logger = null) {
    // $logger будет null, если не может быть разрешён
}
```

## Интеграция с DI контейнером

### Полный пример интеграции

```php
use Bermuda\ParameterResolver\ParameterResolver;

// Создание резолвера со всеми стандартными резолверами
$resolver = ParameterResolver::createDefaults($container);

class UserController
{
    public function createUser(
        #[Config('app.name')] string $appName,
        #[Inject('user.validator')] UserValidator $validator,
        UserRepository $repository,           // Из контейнера по типу
        array $userData,                      // Из переданных параметров
        bool $sendEmail = true,               // Значение по умолчанию
        ?LoggerInterface $logger = null       // Nullable fallback
    ) {
        // Все параметры разрешаются автоматически
        $user = new User($userData);
        
        if ($validator->validate($user)) {
            $repository->save($user);
            
            if ($sendEmail && $logger) {
                $logger->info("Пользователь создан в $appName");
            }
        }
        
        return $user;
    }
}

// Использование
$method = new ReflectionMethod(UserController::class, 'createUser');
$resolvedParams = $resolver->resolve(
    $method->getParameters(),
    ['userData' => $_POST, 'sendEmail' => false]
);

$controller = new UserController();
$user = $controller->createUser(...$resolvedParams);
```

## Управление конфигурацией

Атрибут `Config` поддерживает точечную нотацию и различные источники данных:

```php
use Bermuda\DI\Attribute\Config;

// Простой доступ к конфигурации
function connectDatabase(#[Config('database.host')] string $host) {}

// Со значениями по умолчанию
function getApiKey(#[Config('api.key', 'default-key')] string $key) {}

// Отключение точечной нотации
function getLiteralKey(#[Config('app.debug', explodeDots: false)] bool $debug) {}

// Глубокая вложенность
function getNestedValue(
    #[Config('services.external.api.endpoints.users')] string $endpoint
) {}
```

### Поддержка ArrayAccess

```php
$config = new ArrayObject([
    'database' => ['host' => 'localhost', 'port' => 3306]
]);

$container->set('config', $config);

function connect(#[Config('database.host')] string $host) {
    // Работает с объектами ArrayAccess
}
```

## Продвинутое использование

### Собственные резолверы

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

// Добавление в цепочку резолверов
$resolver = new ParameterResolver([
    new EnvironmentResolver(),
    new ArrayResolver(),
    new DefaultValueResolver()
]);
```

### Поддержка Union типов

```php
function flexibleHandler(string|UserService $handler, int|float $value) {
    // Разрешает первый подходящий тип из union
}

$params = [
    'UserService' => new UserService(),  // Соответствует UserService из union
    'value' => 42                        // Соответствует int из union
];
```

### Приоритеты резолверов

```php
$resolver = new ParameterResolver([
    new ArrayResolver(),           // Приоритет 1: Явные параметры
    new ArrayTypedResolver(),      // Приоритет 2: Сопоставление типов
    new ContainerResolver($container), // Приоритет 3: DI контейнер
    new DefaultValueResolver(),    // Приоритет 4: Значения по умолчанию
    new NullableResolver()         // Приоритет 5: Nullable fallback
]);
```

## Обработка ошибок

```php
use Bermuda\ParameterResolver\ParameterResolutionException;

try {
    $resolvedParams = $resolver->resolve($parameters, $providedParams);
} catch (ParameterResolutionException $e) {
    echo "Не удалось разрешить параметр: " . $e->parameter->getName();
    echo "Предоставленные параметры: " . print_r($e->providedParameters, true);
    echo "Уже разрешённые: " . print_r($e->resolvedParameters, true);
}
```

## Советы по производительности

1. **Упорядочивайте резолверы по частоте использования** - помещайте наиболее часто используемые резолверы первыми
2. **Используйте типизированные параметры** когда возможно для более быстрого сопоставления ArrayTypedResolver
3. **Избегайте глубокой вложенности** в путях Config для лучшей производительности
4. **Кешируйте объекты reflection** при разрешении одинаковых сигнатур многократно

## Требования

- PHP 8.4 или выше
- PSR-11 Container Interface (для ContainerResolver)
- Bermuda DI Attributes (для атрибутов Config/Inject)

## Тестирование

```bash
composer test
```

## Лицензия

MIT License. Подробности в файле [LICENSE](LICENSE).
