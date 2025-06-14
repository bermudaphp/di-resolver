<?php

namespace Bermuda\ParameterResolver\Tests;

use Bermuda\DI\Attribute\Config;
use Bermuda\DI\Attribute\Inject;
use Bermuda\ParameterResolver\ContainerResolver;
use Bermuda\ParameterResolver\ParameterResolutionException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use ReflectionFunction;
use Bermuda\Reflection\Reflection;

class ContainerResolverTest extends TestCase
{
    private ContainerInterface $container;
    private ContainerResolver $resolver;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->resolver = new ContainerResolver($this->container);
    }

    #[Test]
    public function resolvesFromType(): void
    {
        $service = new \DateTime();

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('DateTime')
            ->willReturn(true);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('DateTime')
            ->willReturn($service);

        // Use fully qualified class name to avoid namespace issues
        $fn = fn(\DateTime $date) => null;
        $reflection = new ReflectionFunction($fn);
        $dateParam = $reflection->getParameters()[0];

        $result = $this->resolver->resolve($dateParam);

        $this->assertEquals([0, $service], $result);
    }

    #[Test]
    public function resolvesFromInjectAttribute(): void
    {
        $service = new \stdClass();

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('my.service')
            ->willReturn($service);

        $fn = fn(#[Inject('my.service')] \stdClass $obj) => null;
        $reflection = new ReflectionFunction($fn);
        $objParam = $reflection->getParameters()[0];

        $result = $this->resolver->resolve($objParam);

        $this->assertEquals([0, $service], $result);
    }

    #[Test]
    public function debugAttributeDetection(): void
    {
        $fn = fn(#[Config('database.host')] string $host) => null;
        $reflection = new ReflectionFunction($fn);
        $hostParam = $reflection->getParameters()[0];

        $attributes = $hostParam->getAttributes();

        // Let's see if PHP can detect the attributes
        $this->assertNotEmpty($attributes, 'Config attribute should be detected by PHP reflection');

        $configAttribute = $attributes[0] ?? null;
        $this->assertNotNull($configAttribute);
        $this->assertEquals(Config::class, $configAttribute->getName());

        // Test if we can instantiate the attribute
        $instance = $configAttribute->newInstance();
        $this->assertInstanceOf(Config::class, $instance);
        $this->assertEquals('database.host', $instance->path);
    }

    #[Test]
    public function resolvesFromConfigAttribute(): void
    {

        $config = ['database' => ['host' => 'localhost']];

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $fn = fn(#[Config('database.host')] string $host) => null;
        $reflection = new ReflectionFunction($fn);
        $hostParam = $reflection->getParameters()[0];

        $result = $this->resolver->resolve($hostParam);

        $this->assertEquals([0, 'localhost'], $result);
    }

    #[Test]
    public function throwsExceptionForConfigResolutionError(): void
    {
        $this->expectException(ParameterResolutionException::class);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willThrowException(new \RuntimeException('Config not found'));

        $fn = fn(#[Config('missing.key')] string $value) => null;
        $reflection = new ReflectionFunction($fn);
        $valueParam = $reflection->getParameters()[0];

        try {
            $this->resolver->resolve($valueParam);
        } catch (\TypeError $e) {
            throw $e;
        }
    }

    #[Test]
    public function handlesUnionTypes(): void
    {
        $service = new \DateTime();

        $this->container
            ->expects($this->exactly(2))
            ->method('has')
            ->willReturnMap([
                ['stdClass', false],
                ['DateTime', true]
            ]);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('DateTime')
            ->willReturn($service);

        $fn = fn(\stdClass|\DateTime $obj) => null;
        $reflection = new ReflectionFunction($fn);
        $objParam = $reflection->getParameters()[0];

        $result = $this->resolver->resolve($objParam);

        $this->assertEquals([0, $service], $result);
    }

    #[Test]
    public function returnsNullForBuiltinType(): void
    {
        $fn = fn(string $str) => null;
        $reflection = new ReflectionFunction($fn);
        $strParam = $reflection->getParameters()[0];

        $result = $this->resolver->resolve($strParam);

        $this->assertNull($result);
    }

    #[Test]
    public function returnsNullWhenServiceNotInContainer(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('DateTime')
            ->willReturn(false);

        $fn = fn(\DateTime $date) => null;
        $reflection = new ReflectionFunction($fn);
        $dateParam = $reflection->getParameters()[0];

        $result = $this->resolver->resolve($dateParam);

        $this->assertNull($result);
    }

    #[Test]
    public function createFromContainerFactory(): void
    {
        $resolver = ContainerResolver::createFromContainer($this->container);

        $this->assertInstanceOf(ContainerResolver::class, $resolver);
    }

    #[Test]
    public function resolvesFromInjectAttributeWithoutId(): void
    {
        // Test Inject attribute without id (should return null from resolveFromInjectAttribute)
        $fn = fn(#[Inject()] \stdClass $obj) => null;
        $reflection = new ReflectionFunction($fn);
        $objParam = $reflection->getParameters()[0];

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('stdClass')
            ->willReturn(false);

        $result = $this->resolver->resolve($objParam);

        $this->assertNull($result);
    }
}