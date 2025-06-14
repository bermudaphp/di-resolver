<?php

namespace Bermuda\ParameterResolver\Tests;

use ReflectionFunction;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Bermuda\ParameterResolver\ArrayResolver;

class ArrayResolverTest extends TestCase
{
    private ArrayResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ArrayResolver();
    }

    #[Test]
    public function resolvesByParameterName(): void
    {
        $fn = fn(string $name, int $age) => null;
        $reflection = new ReflectionFunction($fn);
        $nameParam = $reflection->getParameters()[0];

        $providedParams = ['name' => 'John', 'age' => 30];

        $result = $this->resolver->resolve($nameParam, $providedParams);

        $this->assertEquals([0, 'John'], $result);
    }

    #[Test]
    public function resolvesByParameterPosition(): void
    {
        $fn = fn(string $name, int $age) => null;
        $reflection = new ReflectionFunction($fn);
        $nameParam = $reflection->getParameters()[0];

        $providedParams = [0 => 'John', 1 => 30];

        $result = $this->resolver->resolve($nameParam, $providedParams);

        $this->assertEquals([0, 'John'], $result);
    }

    #[Test]
    public function prefersNameOverPosition(): void
    {
        $fn = fn(string $name) => null;
        $reflection = new ReflectionFunction($fn);
        $nameParam = $reflection->getParameters()[0];

        $providedParams = ['name' => 'ByName', 0 => 'ByPosition'];

        $result = $this->resolver->resolve($nameParam, $providedParams);

        $this->assertEquals([0, 'ByName'], $result);
    }

    #[Test]
    public function returnsNullWhenParameterNotFound(): void
    {
        $fn = fn(string $name) => null;
        $reflection = new ReflectionFunction($fn);
        $nameParam = $reflection->getParameters()[0];

        $providedParams = ['other' => 'value'];

        $result = $this->resolver->resolve($nameParam, $providedParams);

        $this->assertNull($result);
    }

    #[Test]
    public function handlesNullValues(): void
    {
        $fn = fn(?string $name) => null;
        $reflection = new ReflectionFunction($fn);
        $nameParam = $reflection->getParameters()[0];

        $providedParams = ['name' => null];

        $result = $this->resolver->resolve($nameParam, $providedParams);

        $this->assertEquals([0, null], $result);
    }

    #[Test]
    public function handlesArrayKeyExistence(): void
    {
        $fn = fn(string $name) => null;
        $reflection = new ReflectionFunction($fn);
        $nameParam = $reflection->getParameters()[0];

        // Using array_key_exists behavior - key exists but value is null
        $providedParams = ['name' => null];

        $result = $this->resolver->resolve($nameParam, $providedParams);

        $this->assertEquals([0, null], $result);
    }
}
