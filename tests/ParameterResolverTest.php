<?php

declare(strict_types=1);

namespace Bermuda\ParameterResolver\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Bermuda\ParameterResolver\ParameterResolver;
use Bermuda\ParameterResolver\ParameterResolverInterface;
use Bermuda\ParameterResolver\ParameterResolutionException;
use Bermuda\ParameterResolver\ArrayResolver;
use Bermuda\ParameterResolver\ArrayTypedResolver;
use Bermuda\ParameterResolver\ContainerResolver;
use Bermuda\ParameterResolver\DefaultValueResolver;
use Bermuda\ParameterResolver\NullableResolver;
use Bermuda\DI\Attribute\Config;
use Bermuda\DI\Attribute\Inject;
use Psr\Container\ContainerInterface;
use ReflectionParameter;
use ReflectionFunction;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

final class ParameterResolverTest extends TestCase
{
    private ParameterResolver $resolver;
    private ParameterResolverInterface&MockObject $mockResolver1;
    private ParameterResolverInterface&MockObject $mockResolver2;
    private ContainerInterface&MockObject $mockContainer;

    protected function setUp(): void
    {
        $this->mockResolver1 = $this->createMock(ParameterResolverInterface::class);
        $this->mockResolver2 = $this->createMock(ParameterResolverInterface::class);
        $this->mockContainer = $this->createMock(ContainerInterface::class);
        $this->resolver = new ParameterResolver([$this->mockResolver1, $this->mockResolver2]);
    }

    #[Test]
    #[TestDox('Should create empty resolver when no resolvers provided')]
    public function createsEmptyResolverWhenNoResolversProvided(): void
    {
        $resolver = new ParameterResolver();

        $this->assertCount(0, $resolver);
        $this->assertEmpty(iterator_to_array($resolver->getIterator()));
    }

    #[Test]
    #[TestDox('Should initialize with provided resolvers')]
    public function initializesWithProvidedResolvers(): void
    {
        $this->assertCount(2, $this->resolver);

        $resolvers = iterator_to_array($this->resolver->getIterator());
        $this->assertSame($this->mockResolver1, $resolvers[0]);
        $this->assertSame($this->mockResolver2, $resolvers[1]);
    }

    #[Test]
    #[TestDox('Should add resolver to end by default')]
    public function addsResolverToEndByDefault(): void
    {
        $mockResolver3 = $this->createMock(ParameterResolverInterface::class);
        $this->resolver->addResolver($mockResolver3);

        $this->assertCount(3, $this->resolver);

        $resolvers = iterator_to_array($this->resolver->getIterator());
        $this->assertSame($mockResolver3, $resolvers[2]);
    }

    #[Test]
    #[TestDox('Should prepend resolver when requested')]
    public function prependsResolverWhenRequested(): void
    {
        $mockResolver3 = $this->createMock(ParameterResolverInterface::class);
        $this->resolver->addResolver($mockResolver3, true);

        $this->assertCount(3, $this->resolver);

        $resolvers = iterator_to_array($this->resolver->getIterator());
        $this->assertSame($mockResolver3, $resolvers[0]);
        $this->assertSame($this->mockResolver1, $resolvers[1]);
        $this->assertSame($this->mockResolver2, $resolvers[2]);
    }

    #[Test]
    #[TestDox('Should detect resolver presence by instance')]
    public function detectsResolverPresenceByInstance(): void
    {
        // Используем анонимные классы с уникальными реализациями
        $resolver1 = new class implements ParameterResolverInterface {
            public function resolve(\ReflectionParameter $parameter, array $providedParameters = [], array $resolvedParameters = []): ?array {
                return null;
            }
        };

        $resolver2 = new class implements ParameterResolverInterface {
            public function resolve(\ReflectionParameter $parameter, array $providedParameters = [], array $resolvedParameters = []): ?array {
                return null;
            }
        };

        $parameterResolver = new ParameterResolver([$resolver1, $resolver2]);

        $this->assertTrue($parameterResolver->hasResolver($resolver1));
        $this->assertTrue($parameterResolver->hasResolver($resolver2));

        $nonExistentResolver = new class implements ParameterResolverInterface {
            public function resolve(\ReflectionParameter $parameter, array $providedParameters = [], array $resolvedParameters = []): ?array {
                return null;
            }
        };
        $this->assertFalse($parameterResolver->hasResolver($nonExistentResolver));
    }

    #[Test]
    #[TestDox('Should detect resolver presence by class name')]
    public function detectsResolverPresenceByClassName(): void
    {
        $this->assertTrue($this->resolver->hasResolver($this->mockResolver1::class));
        $this->assertTrue($this->resolver->hasResolver($this->mockResolver2::class));
        $this->assertFalse($this->resolver->hasResolver('NonExistentClass'));
    }

    #[Test]
    #[TestDox('Should resolve parameter using first successful resolver')]
    public function resolvesParameterUsingFirstSuccessfulResolver(): void
    {
        $parameter = $this->createTestParameter();
        $providedParameters = ['test' => 'value'];

        $this->mockResolver1
            ->expects($this->once())
            ->method('resolve')
            ->with($parameter, $providedParameters)
            ->willReturn(null);

        $this->mockResolver2
            ->expects($this->once())
            ->method('resolve')
            ->with($parameter, $providedParameters)
            ->willReturn([0, 'resolved_value']);

        $result = $this->resolver->resolveParameter($parameter, $providedParameters);

        $this->assertEquals([0, 'resolved_value'], $result);
    }

    #[Test]
    #[TestDox('Should throw exception when no resolver can resolve parameter')]
    public function throwsExceptionWhenNoResolverCanResolveParameter(): void
    {
        $parameter = $this->createTestParameter();

        $this->mockResolver1->method('resolve')->willReturn(null);
        $this->mockResolver2->method('resolve')->willReturn(null);

        $this->expectException(ParameterResolutionException::class);
        $this->expectExceptionMessage("Can't resolve parameter");

        $this->resolver->resolveParameter($parameter);
    }

    #[Test]
    #[TestDox('Should resolve multiple parameters correctly')]
    public function resolvesMultipleParametersCorrectly(): void
    {
        $parameters = $this->createMultipleTestParameters();

        $this->mockResolver1
            ->method('resolve')
            ->willReturnCallback(fn($param) => match ($param->getName()) {
                'param1' => [0, 'value1'],
                default => null
            });

        $this->mockResolver2
            ->method('resolve')
            ->willReturnCallback(fn($param) => match ($param->getName()) {
                'param2' => [1, 'value2'],
                default => null
            });

        $result = $this->resolver->resolve($parameters);

        $this->assertEquals([
            0 => 'value1',
            1 => 'value2'
        ], $result);
    }

    #[Test]
    #[TestDox('Should create default resolver with all standard resolvers')]
    public function createsDefaultResolverWithAllStandardResolvers(): void
    {
        $resolver = ParameterResolver::createDefaults($this->mockContainer);

        $this->assertInstanceOf(ParameterResolver::class, $resolver);
        $this->assertCount(5, $resolver);

        $resolvers = iterator_to_array($resolver->getIterator());
        $this->assertInstanceOf(ArrayResolver::class, $resolvers[0]);
        $this->assertInstanceOf(ArrayTypedResolver::class, $resolvers[1]);
        $this->assertInstanceOf(ContainerResolver::class, $resolvers[2]);
        $this->assertInstanceOf(DefaultValueResolver::class, $resolvers[3]);
        $this->assertInstanceOf(NullableResolver::class, $resolvers[4]);
    }

    #[Test]
    #[TestDox('ArrayResolver should resolve by parameter name')]
    public function arrayResolverResolvesParameterByName(): void
    {
        $resolver = new ParameterResolver([new ArrayResolver()]);
        $parameters = $this->createNamedTestParameters();
        $providedParameters = ['username' => 'john_doe', 'email' => 'john@example.com'];

        $result = $resolver->resolve($parameters, $providedParameters);

        $this->assertEquals([
            0 => 'john_doe',
            1 => 'john@example.com'
        ], $result);
    }

    #[Test]
    #[TestDox('ArrayResolver should resolve by parameter position')]
    public function arrayResolverResolvesParameterByPosition(): void
    {
        $resolver = new ParameterResolver([new ArrayResolver()]);
        $parameters = $this->createPositionalTestParameters();
        $providedParameters = [0 => 'first_value', 1 => 'second_value'];

        $result = $resolver->resolve($parameters, $providedParameters);

        $this->assertEquals([
            0 => 'first_value',
            1 => 'second_value'
        ], $result);
    }

    #[Test]
    #[TestDox('ArrayTypedResolver should resolve by parameter type')]
    public function arrayTypedResolverResolvesParameterByType(): void
    {
        $resolver = new ParameterResolver([new ArrayTypedResolver()]);
        $parameters = $this->createTypedTestParameters();
        $stdClassObj = new stdClass();
        $providedParameters = [
            'stdClass' => $stdClassObj,
        ];

        $result = $resolver->resolve($parameters, $providedParameters);

        $this->assertSame($stdClassObj, $result[0]);
    }

    #[Test]
    #[TestDox('ArrayTypedResolver should find object by scanning array')]
    public function arrayTypedResolverFindsObjectByScanning(): void
    {
        $resolver = new ParameterResolver([new ArrayTypedResolver()]);
        $parameters = $this->createTypedTestParameters();
        $stdClassObj = new stdClass();
        $providedParameters = [
            'other_service' => 'string_value',
            'target_service' => $stdClassObj,
        ];

        $result = $resolver->resolve($parameters, $providedParameters);

        $this->assertSame($stdClassObj, $result[0]);
    }

    #[Test]
    #[TestDox('DefaultValueResolver should resolve parameter default values')]
    public function defaultValueResolverResolvesParameterDefaultValues(): void
    {
        $resolver = new ParameterResolver([new DefaultValueResolver()]);
        $parameters = $this->createParametersWithDefaults();

        $result = $resolver->resolve($parameters);

        $this->assertEquals([
            0 => 'default_name',
            1 => 25
        ], $result);
    }

    #[Test]
    #[TestDox('NullableResolver should resolve nullable parameters with null')]
    public function nullableResolverResolvesNullableParametersWithNull(): void
    {
        $resolver = new ParameterResolver([new NullableResolver()]);
        $parameters = $this->createNullableTestParameters();

        $result = $resolver->resolve($parameters);

        $this->assertEquals([
            0 => null,
            1 => null
        ], $result);
    }

    #[Test]
    #[TestDox('ContainerResolver should resolve from container by type')]
    public function containerResolverResolvesFromContainerByType(): void
    {
        $service = new stdClass();
        $this->mockContainer
            ->method('has')
            ->with('stdClass')
            ->willReturn(true);

        $this->mockContainer
            ->method('get')
            ->with('stdClass')
            ->willReturn($service);

        $resolver = new ParameterResolver([new ContainerResolver($this->mockContainer)]);
        $parameters = $this->createContainerTestParameters();

        $result = $resolver->resolve($parameters);

        $this->assertSame($service, $result[0]);
    }

    #[Test]
    #[TestDox('ContainerResolver should resolve with Inject attribute')]
    public function containerResolverResolvesWithInjectAttribute(): void
    {
        $service = new stdClass();
        $this->mockContainer
            ->method('get')
            ->with('custom_service_id')
            ->willReturn($service);

        $resolver = new ParameterResolver([new ContainerResolver($this->mockContainer)]);
        $parameters = $this->createInjectAttributeTestParameters();

        $result = $resolver->resolve($parameters);

        $this->assertSame($service, $result[0]);
    }

    #[Test]
    #[TestDox('ContainerResolver should handle Inject attribute without ID')]
    public function containerResolverHandlesInjectAttributeWithoutId(): void
    {
        $resolver = new ParameterResolver([new ContainerResolver($this->mockContainer)]);
        $function = new ReflectionFunction(
            function(#[Inject()] $service) {}
        );
        $parameters = $function->getParameters();

        $this->expectException(ParameterResolutionException::class);
        $resolver->resolve($parameters);
    }

    #[Test]
    #[TestDox('ContainerResolver should resolve with Config attribute')]
    public function containerResolverResolvesWithConfigAttribute(): void
    {
        $config = [
            'database' => [
                'host' => 'localhost',
                'port' => 3306
            ]
        ];

        $this->mockContainer
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $resolver = new ParameterResolver([new ContainerResolver($this->mockContainer)]);
        $parameters = $this->createConfigAttributeTestParameters();

        $result = $resolver->resolve($parameters);

        $this->assertEquals('localhost', $result[0]);
    }

    #[Test]
    #[TestDox('ContainerResolver should not resolve builtin types')]
    public function containerResolverDoesNotResolveBuiltinTypes(): void
    {
        $resolver = new ParameterResolver([new ContainerResolver($this->mockContainer)]);
        $function = new ReflectionFunction(function(string $value) {});
        $parameters = $function->getParameters();

        $this->expectException(ParameterResolutionException::class);
        $resolver->resolve($parameters);
    }

    #[Test]
    #[TestDox('ContainerResolver should handle missing container entries')]
    public function containerResolverHandlesMissingContainerEntries(): void
    {
        $this->mockContainer
            ->method('has')
            ->with('stdClass')
            ->willReturn(false);

        $resolver = new ParameterResolver([new ContainerResolver($this->mockContainer)]);
        $parameters = $this->createContainerTestParameters();

        // ОЖИДАНИЕ: контейнер не содержит сервис → параметр не разрешается
        $this->expectException(ParameterResolutionException::class);
        $resolver->resolve($parameters);
    }

    #[Test]
    #[TestDox('ContainerResolver should handle parameters without types correctly')]
    public function containerResolverHandlesParametersWithoutTypes(): void
    {
        $resolver = new ParameterResolver([new ContainerResolver($this->mockContainer)]);
        $function = new ReflectionFunction(function($untyped) {}); // Без типа
        $parameters = $function->getParameters();

        $this->expectException(ParameterResolutionException::class);
        $resolver->resolve($parameters);
    }

    #[Test]
    #[TestDox('Should properly isolate ContainerResolver behavior')]
    public function properlyIsolatesContainerResolverBehavior(): void
    {
        // Тестируем ТОЛЬКО ContainerResolver без fallback resolvers
        $resolver = new ParameterResolver([new ContainerResolver($this->mockContainer)]);

        $service = new stdClass();
        $this->mockContainer->method('has')->with('stdClass')->willReturn(true);
        $this->mockContainer->method('get')->with('stdClass')->willReturn($service);

        $function = new ReflectionFunction(function(stdClass $service) {});
        $result = $resolver->resolve($function->getParameters());
        $this->assertSame($service, $result[0]);

        $this->mockContainer = $this->createMock(ContainerInterface::class);
        $this->mockContainer->method('has')->with('stdClass')->willReturn(false);
        $resolver = new ParameterResolver([new ContainerResolver($this->mockContainer)]);

        $this->expectException(ParameterResolutionException::class);
        $resolver->resolve($function->getParameters());
    }

    #[Test]
    #[TestDox('Should demonstrate proper resolver chain behavior')]
    public function demonstratesProperResolverChainBehavior(): void
    {
        $resolver = new ParameterResolver([
            new ArrayResolver(),           
            new DefaultValueResolver(),    
            new NullableResolver()        
        ]);

        $function = new ReflectionFunction(function(
            string $explicit,           
            string $withDefault = 'def', 
            ?int $nullable = null      
        ) {});

        $providedParameters = [
            'explicit' => 'provided_value'
        ];

        $result = $resolver->resolve($function->getParameters(), $providedParameters);

        $this->assertEquals('provided_value', $result[0]); // ArrayResolver
        $this->assertEquals('def', $result[1]);           // DefaultValueResolver
        $this->assertNull($result[2]);                    // NullableResolver
    }
    #[Test]
    #[TestDox('Should resolve parameters using all resolvers in priority order')]
    public function resolvesParametersUsingAllResolversInPriorityOrder(): void
    {
        $resolver = ParameterResolver::createDefaults($this->mockContainer);

        $service = new stdClass();
        $this->mockContainer->method('has')->willReturn(true);
        $this->mockContainer->method('get')->willReturn($service);

        $parameters = $this->createComprehensiveTestParameters();
        $providedParameters = [
            'explicitParam' => 'explicit_value',
            'stdClass' => $service
        ];

        $result = $resolver->resolve($parameters, $providedParameters);

        $this->assertEquals('explicit_value', $result[0]);
        $this->assertSame($service, $result[1]);
        $this->assertSame($service, $result[2]); 
        $this->assertEquals('default_value', $result[3]);
        $this->assertNull($result[4]);
    }

    #[Test]
    #[TestDox('Should respect resolver order and use first successful match')]
    public function respectsResolverOrderAndUsesFirstSuccessfulMatch(): void
    {
        $resolver = new ParameterResolver([
            new ArrayResolver(),
            new ArrayTypedResolver()
        ]);

        $function = new ReflectionFunction(function($ambiguous) {});
        $parameters = $function->getParameters();
        $service = new stdClass();
        $providedParameters = [
            'ambiguous' => 'name_match',
            'stdClass' => $service
        ];

        $result = $resolver->resolve($parameters, $providedParameters);
        $this->assertEquals('name_match', $result[0]);
    }

    #[Test]
    #[TestDox('Should handle Config attribute with explodeDots disabled')]
    public function handlesConfigAttributeWithExplodeDotsDisabled(): void
    {
        $config = [
            'database.host' => 'literal_key_value'
        ];

        $this->mockContainer
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $resolver = new ParameterResolver([new ContainerResolver($this->mockContainer)]);
        $function = new ReflectionFunction(
            function(#[Config('database.host', explodeDots: false)] string $host) {}
        );
        $parameters = $function->getParameters();

        $result = $resolver->resolve($parameters);

        $this->assertEquals('literal_key_value', $result[0]);
    }

    #[Test]
    #[TestDox('Should throw exception when Config key not found')]
    public function throwsExceptionWhenConfigKeyNotFound(): void
    {
        $config = [];

        $this->mockContainer
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $resolver = new ParameterResolver([new ContainerResolver($this->mockContainer)]);
        $function = new ReflectionFunction(
            function(#[Config('nonexistent.key')] string $value) {}
        );
        $parameters = $function->getParameters();

        $this->expectException(ParameterResolutionException::class);
        $resolver->resolve($parameters);
    }

    #[Test]
    #[TestDox('Should resolve parameters with ArrayAccess config')]
    public function resolvesParametersWithArrayAccessConfig(): void
    {
        $config = new class implements \ArrayAccess {
            private array $data = [
                'app' => ['name' => 'ArrayAccessApp']
            ];

            public function offsetExists($offset): bool {
                return isset($this->data[$offset]);
            }

            public function offsetGet($offset): mixed {
                return $this->data[$offset];
            }

            public function offsetSet($offset, $value): void {
                $this->data[$offset] = $value;
            }

            public function offsetUnset($offset): void {
                unset($this->data[$offset]);
            }
        };

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $resolver = new ParameterResolver([new ContainerResolver($containerMock)]);
        $function = new ReflectionFunction(
            function(#[Config('app.name')] string $appName) {}
        );
        $parameters = $function->getParameters();

        $result = $resolver->resolve($parameters);

        $this->assertEquals('ArrayAccessApp', $result[0]);
    }

    #[Test]
    #[TestDox('Should handle complex scenario with mixed resolution strategies')]
    public function handlesComplexScenarioWithMixedResolutionStrategies(): void
    {
        // Setup complex container scenario
        $configService = [
            'app' => ['name' => 'TestApp', 'debug' => true],
            'database' => ['host' => 'localhost']
        ];

        $userService = new stdClass();
        $userService->name = 'TestUser';

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock
            ->method('has')
            ->willReturnMap([
                ['config', true],
                ['stdClass', true],
                ['custom_logger', true]
            ]);

        $customLogger = new stdClass();
        $customLogger->type = 'logger';

        $containerMock
            ->method('get')
            ->willReturnMap([
                ['config', $configService],
                ['stdClass', $userService],
                ['custom_logger', $customLogger]
            ]);

        $resolver = ParameterResolver::createDefaults($containerMock);
        $parameters = $this->createComplexTestParameters();
        $providedParameters = ['explicitValue' => 42];

        $result = $resolver->resolve($parameters, $providedParameters);

        $this->assertEquals(42, $result[0]);
        $this->assertEquals('TestApp', $result[1]); 
        $this->assertSame($customLogger, $result[2]);
        $this->assertSame($userService, $result[3]); 
        $this->assertEquals('fallback', $result[4]); 
        $this->assertNull($result[5]);
    }

    #[Test]
    #[TestDox('Should handle union types with ArrayTypedResolver')]
    public function handlesUnionTypesWithArrayTypedResolver(): void
    {
        $resolver = new ParameterResolver([new ArrayTypedResolver()]);
        $function = new ReflectionFunction(function(string|stdClass $value) {});
        $parameters = $function->getParameters();

        $stdClassObj = new stdClass();
        $providedParameters = ['stdClass' => $stdClassObj];
        $result = $resolver->resolve($parameters, $providedParameters);

        $this->assertSame($stdClassObj, $result[0]);
    }

    #[Test]
    #[TestDox('Should handle union types with ContainerResolver')]
    public function handlesUnionTypesWithContainerResolver(): void
    {
        $service = new stdClass();
        $this->mockContainer
            ->method('has')
            ->willReturnMap([
                ['string', false],
                ['stdClass', true]
            ]);

        $this->mockContainer
            ->method('get')
            ->with('stdClass')
            ->willReturn($service);

        $resolver = new ParameterResolver([new ContainerResolver($this->mockContainer)]);
        $function = new ReflectionFunction(function(string|stdClass $value) {});
        $parameters = $function->getParameters();

        $result = $resolver->resolve($parameters);

        $this->assertSame($service, $result[0]);
    }

    #[Test]
    #[TestDox('Should handle builtin types correctly in ArrayTypedResolver')]
    public function handlesBuiltinTypesCorrectlyInArrayTypedResolver(): void
    {
        $resolver = new ParameterResolver([
            new ArrayTypedResolver(),
            new DefaultValueResolver()
        ]);
        $function = new ReflectionFunction(function(string $value = 'default') {});
        $parameters = $function->getParameters();

        $providedParameters = ['some_value' => 'test'];

        $result = $resolver->resolve($parameters, $providedParameters);
        $this->assertEquals('default', $result[0]);
    }

    #[Test]
    #[TestDox('Should handle null types in resolvers gracefully')]
    public function handlesNullTypesInResolversGracefully(): void
    {
        $resolver = new ParameterResolver([
            new ArrayTypedResolver(),
            new DefaultValueResolver()
        ]);

        $function = new ReflectionFunction(function($untyped = 'fallback') {});
        $parameters = $function->getParameters();

        $result = $resolver->resolve($parameters, []);
        $this->assertEquals('fallback', $result[0]);
    }

    #[Test]
    #[TestDox('Should handle complex dependency injection scenario')]
    public function handlesComplexDependencyInjectionScenario(): void
    {
        $configData = [
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'mysql' => [
                        'host' => 'localhost',
                        'port' => 3306,
                        'database' => 'test_db'
                    ]
                ]
            ],
            'app' => [
                'name' => 'TestApplication',
                'version' => '1.0.0',
                'debug' => true
            ]
        ];

        $userRepository = new stdClass();
        $userRepository->users = ['user1', 'user2'];

        $logger = new stdClass();
        $logger->logs = [];

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock
            ->method('has')
            ->willReturnMap([
                ['config', true],
                ['stdClass', true],
                ['custom_logger', true]
            ]);

        $containerMock
            ->method('get')
            ->willReturnMap([
                ['config', $configData],
                ['stdClass', $userRepository],
                ['custom_logger', $logger]
            ]);

        $resolver = ParameterResolver::createDefaults($containerMock);
        $function = new ReflectionFunction(function(
            #[Config('database.connections.mysql.host')] string $dbHost,
            #[Config('app.name')] string $appName,
            #[Inject('custom_logger')] object $logger,
            stdClass $userRepo,
            ?object $cache = null,
            bool $debug = false
        ) {});

        $parameters = $function->getParameters();
        $providedParameters = ['debug' => true];
        $result = $resolver->resolve($parameters, $providedParameters);

        $this->assertEquals('localhost', $result[0]);
        $this->assertEquals('TestApplication', $result[1]);
        $this->assertSame($logger, $result[2]);
        $this->assertSame($userRepository, $result[3]);
        $this->assertNull($result[4]);
        $this->assertTrue($result[5]);
    }

    #[Test]
    #[TestDox('Should throw exception with proper context when resolution fails')]
    public function throwsExceptionWithProperContextWhenResolutionFails(): void
    {
        $resolver = new ParameterResolver([new ArrayResolver()]);
        $parameters = $this->createUnresolvableTestParameters();

        try {
            $resolver->resolve($parameters);
            $this->fail('Expected ParameterResolutionException to be thrown');
        } catch (ParameterResolutionException $e) {
            $this->assertInstanceOf(ReflectionParameter::class, $e->parameter);
            $this->assertIsArray($e->providedParameters);
            $this->assertIsArray($e->resolvedParameters);
            $this->assertStringContainsString("Can't resolve parameter", $e->getMessage());
        }
    }

    #[Test]
    #[TestDox('Should handle partial resolution gracefully')]
    public function handlesPartialResolutionGracefully(): void
    {
        $resolver = new ParameterResolver([
            new ArrayResolver(),
            new DefaultValueResolver()
        ]);

        $parameters = $this->createPartiallyResolvableTestParameters();
        $providedParameters = ['resolvable' => 'resolved_value'];

        $result = $resolver->resolve($parameters, $providedParameters);

        $this->assertEquals([
            0 => 'resolved_value',
            1 => 'default_fallback'
        ], $result);
    }

    #[Test]
    #[TestDox('Should maintain resolver state across multiple resolutions')]
    public function maintainsResolverStateAcrossMultipleResolutions(): void
    {
        $statefulResolver = new class implements ParameterResolverInterface {
            private int $callCount = 0;

            public function resolve(\ReflectionParameter $parameter, array $providedParameters = [], array $resolvedParameters = []): ?array
            {
                $this->callCount++;
                if ($parameter->getName() === 'counter') {
                    return [$parameter->getPosition(), $this->callCount];
                }
                return null;
            }

            public function getCallCount(): int { return $this->callCount; }
        };

        $resolver = new ParameterResolver([$statefulResolver]);

        $function1 = new ReflectionFunction(function($counter) {});
        $function2 = new ReflectionFunction(function($counter) {});

        $result1 = $resolver->resolve($function1->getParameters());
        $result2 = $resolver->resolve($function2->getParameters());

        $this->assertEquals(1, $result1[0]);
        $this->assertEquals(2, $result2[0]);
        $this->assertEquals(2, $statefulResolver->getCallCount());
    }

    #[Test]
    #[TestDox('Should handle type mismatch validation gracefully')]
    public function handlesTypeMismatchValidationGracefully(): void
    {
        $badResolver = new class implements ParameterResolverInterface {
            public function resolve(\ReflectionParameter $parameter, array $providedParameters = [], array $resolvedParameters = []): ?array
            {
                return [$parameter->getPosition(), 'not_an_int'];
            }
        };

        $resolver = new ParameterResolver([$badResolver]);
        $function = new ReflectionFunction(function(int $number) {});
        $parameters = $function->getParameters();

        $this->expectException(ParameterResolutionException::class);
        $this->expectExceptionMessage('must be of type int');

        $resolver->resolve($parameters);
    }

    private function createTestParameter(string $name = 'testParam', int $position = 0): ReflectionParameter
    {
        $function = new ReflectionFunction(function($testParam) {});
        return $function->getParameters()[0];
    }

    private function createMultipleTestParameters(): array
    {
        $function = new ReflectionFunction(function($param1, $param2) {});
        return $function->getParameters();
    }

    private function createNamedTestParameters(): array
    {
        $function = new ReflectionFunction(function($username, $email) {});
        return $function->getParameters();
    }

    private function createPositionalTestParameters(): array
    {
        $function = new ReflectionFunction(function($first, $second) {});
        return $function->getParameters();
    }

    private function createTypedTestParameters(): array
    {
        $function = new ReflectionFunction(function(stdClass $obj) {});
        return $function->getParameters();
    }

    private function createParametersWithDefaults(): array
    {
        $function = new ReflectionFunction(function($name = 'default_name', $age = 25) {});
        return $function->getParameters();
    }

    private function createNullableTestParameters(): array
    {
        $function = new ReflectionFunction(function(?string $name, ?int $age) {});
        return $function->getParameters();
    }

    private function createContainerTestParameters(): array
    {
        $function = new ReflectionFunction(function(stdClass $service) {});
        return $function->getParameters();
    }

    private function createInjectAttributeTestParameters(): array
    {
        $function = new ReflectionFunction(
            function(#[Inject('custom_service_id')] $service) {}
        );
        return $function->getParameters();
    }

    private function createConfigAttributeTestParameters(): array
    {
        $function = new ReflectionFunction(
            function(#[Config('database.host')] string $host) {}
        );
        return $function->getParameters();
    }

    private function createComplexTestParameters(): array
    {
        $function = new ReflectionFunction(function(
            $explicitValue,
            #[Config('app.name')] string $appName,
            #[Inject('custom_logger')] $logger,
            stdClass $userService, 
            $fallbackParam = 'fallback',
            ?string $optionalParam = null
        ) {});
        return $function->getParameters();
    }

    private function createComprehensiveTestParameters(): array
    {
        $function = new ReflectionFunction(function(
            $explicitParam,
            stdClass $typedParam,
            stdClass $containerParam,
            $defaultParam = 'default_value',
            ?string $nullableParam = null
        ) {});
        return $function->getParameters();
    }

    private function createUnresolvableTestParameters(): array
    {
        $function = new ReflectionFunction(function($unresolvableParam) {});
        return $function->getParameters();
    }

    private function createPartiallyResolvableTestParameters(): array
    {
        $function = new ReflectionFunction(function(
            $resolvable,
            $withDefault = 'default_fallback'
        ) {});
        return $function->getParameters();
    }
    
    public static function resolverOrderProvider(): array
    {
        return [
            'array_then_default' => [
                [ArrayResolver::class, DefaultValueResolver::class],
                ['param' => 'array_value'],
                'array_value'
            ],
            'default_then_array' => [
                [DefaultValueResolver::class, ArrayResolver::class],
                [],
                'default_param_value'
            ]
        ];
    }

    #[Test]
    #[DataProvider('resolverOrderProvider')]
    #[TestDox('Should respect resolver order in resolution')]
    public function respectsResolverOrderInResolution(array $resolverClasses, array $provided, string $expected): void
    {
        $resolvers = array_map(fn($class) => match($class) {
            ArrayResolver::class => new ArrayResolver(),
            DefaultValueResolver::class => new DefaultValueResolver(),
            default => throw new \InvalidArgumentException("Unknown resolver: $class")
        }, $resolverClasses);

        $resolver = new ParameterResolver($resolvers);
        $function = new ReflectionFunction(function($param = 'default_param_value') {});
        $parameters = $function->getParameters();

        $result = $resolver->resolve($parameters, $provided);

        $this->assertEquals($expected, $result[0]);
    }

    #[Test]
    #[TestDox('Should handle performance with many parameters efficiently')]
    public function handlesPerformanceWithManyParametersEfficiently(): void
    {
        $resolver = new ParameterResolver([new ArrayResolver(), new DefaultValueResolver()]);
        $testClass = new class {
            public function manyParams(
                $p1 = 'v1', $p2 = 'v2', $p3 = 'v3', $p4 = 'v4', $p5 = 'v5',
                $p6 = 'v6', $p7 = 'v7', $p8 = 'v8', $p9 = 'v9', $p10 = 'v10',
                $p11 = 'v11', $p12 = 'v12', $p13 = 'v13', $p14 = 'v14', $p15 = 'v15',
                $p16 = 'v16', $p17 = 'v17', $p18 = 'v18', $p19 = 'v19', $p20 = 'v20'
            ) {}
        };

        $method = new ReflectionMethod($testClass, 'manyParams');
        $parameters = $method->getParameters();
        
        $providedParameters = [
            'p1' => 'override1',
            'p5' => 'override5',
            'p10' => 'override10'
        ];

        $startTime = microtime(true);
        $result = $resolver->resolve($parameters, $providedParameters);
        $endTime = microtime(true);

        $this->assertCount(20, $result);
        $this->assertEquals('override1', $result[0]);
        $this->assertEquals('v2', $result[1]);
        $this->assertEquals('override5', $result[4]);
        $this->assertEquals('override10', $result[9]);

        $this->assertLessThan(0.1, $endTime - $startTime);
    }
}
