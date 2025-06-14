<?php

namespace Bermuda\ParameterResolver\Tests;

use ReflectionFunction;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Bermuda\ParameterResolver\DefaultValueResolver;

class DefaultValueResolverTest extends TestCase
{
    private DefaultValueResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new DefaultValueResolver();
    }

    #[Test]
    public function resolvesDefaultValue(): void
    {
        $fn = fn(string $name = 'default') => null;
        $reflection = new ReflectionFunction($fn);
        $nameParam = $reflection->getParameters()[0];

        $result = $this->resolver->resolve($nameParam);

        $this->assertEquals([0, 'default'], $result);
    }

    #[Test]
    public function resolvesNullDefaultValue(): void
    {
        $fn = fn(?string $name = null) => null;
        $reflection = new ReflectionFunction($fn);
        $nameParam = $reflection->getParameters()[0];

        $result = $this->resolver->resolve($nameParam);

        $this->assertEquals([0, null], $result);
    }

    #[Test]
    public function returnsNullWhenNoDefaultValue(): void
    {
        $fn = fn(string $name) => null;
        $reflection = new ReflectionFunction($fn);
        $nameParam = $reflection->getParameters()[0];

        $result = $this->resolver->resolve($nameParam);

        $this->assertNull($result);
    }

    #[Test]
    public function resolvesComplexDefaultValues(): void
    {
        $fn = fn(array $items = ['a', 'b']) => null;
        $reflection = new ReflectionFunction($fn);
        $itemsParam = $reflection->getParameters()[0];

        $result = $this->resolver->resolve($itemsParam);

        $this->assertEquals([0, ['a', 'b']], $result);
    }
}