<?php

namespace Bermuda\ParameterResolver\Tests;

use ReflectionFunction;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Bermuda\ParameterResolver\NullableResolver;

class NullableResolverTest extends TestCase
{
    private NullableResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new NullableResolver();
    }

    #[Test]
    public function resolvesNullableParameter(): void
    {
        $fn = fn(?string $name) => null;
        $reflection = new ReflectionFunction($fn);
        $nameParam = $reflection->getParameters()[0];

        $result = $this->resolver->resolve($nameParam);

        $this->assertEquals([0, null], $result);
    }

    #[Test]
    public function resolvesUnionWithNull(): void
    {
        $fn = fn(string|null $name) => null;
        $reflection = new ReflectionFunction($fn);
        $nameParam = $reflection->getParameters()[0];

        $result = $this->resolver->resolve($nameParam);

        $this->assertEquals([0, null], $result);
    }

    #[Test]
    public function returnsNullForNonNullableParameter(): void
    {
        $fn = fn(string $name) => null;
        $reflection = new ReflectionFunction($fn);
        $nameParam = $reflection->getParameters()[0];

        $result = $this->resolver->resolve($nameParam);

        $this->assertNull($result);
    }
}