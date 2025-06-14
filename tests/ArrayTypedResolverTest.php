<?php

namespace Bermuda\ParameterResolver\Tests;

use ReflectionFunction;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Bermuda\ParameterResolver\ArrayTypedResolver;

class ArrayTypedResolverTest extends TestCase
{
    private ArrayTypedResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ArrayTypedResolver();
    }

    #[Test]
    public function resolvesByTypeName(): void
    {
        $fn = fn(\DateTime $date) => null;
        $reflection = new ReflectionFunction($fn);
        $dateParam = $reflection->getParameters()[0];

        $dateValue = new \DateTime();

        $typeName = $dateParam->getType()->getName();
        $providedParams = [$typeName => $dateValue];

        $result = $this->resolver->resolve($dateParam, $providedParams);

        $this->assertEquals([0, $dateValue], $result);
    }

    #[Test]
    public function resolvesByInstanceofCheck(): void
    {
        $fn = fn(\DateTime $date) => null;
        $reflection = new ReflectionFunction($fn);
        $dateParam = $reflection->getParameters()[0];

        $dateValue = new \DateTime();
        $providedParams = ['some_date' => $dateValue, 'other' => 'value'];

        $result = $this->resolver->resolve($dateParam, $providedParams);

        $this->assertEquals([0, $dateValue], $result);
    }

    #[Test]
    public function handlesUnionTypes(): void
    {
        $fn = fn(\DateTime|\DateTimeImmutable $date) => null;
        $reflection = new ReflectionFunction($fn);
        $dateParam = $reflection->getParameters()[0];

        $dateValue = new \DateTimeImmutable();
        $providedParams = ['some_value' => $dateValue];

        $result = $this->resolver->resolve($dateParam, $providedParams);

        $this->assertEquals($dateValue, $result[1]);
        $this->assertEquals([0 => 0, 1 => $dateValue], $result);
    }

    #[Test]
    public function returnsNullForBuiltinTypes(): void
    {
        $fn = fn(string $str) => null;
        $reflection = new ReflectionFunction($fn);
        $strParam = $reflection->getParameters()[0];

        $providedParams = ['string' => 'value'];

        $result = $this->resolver->resolve($strParam, $providedParams);

        $this->assertNull($result);
    }

    #[Test]
    public function returnsNullWhenNoMatch(): void
    {
        $fn = fn(\DateTime $date) => null;
        $reflection = new ReflectionFunction($fn);
        $dateParam = $reflection->getParameters()[0];

        $providedParams = ['other' => 'value', 'another' => 123];

        $result = $this->resolver->resolve($dateParam, $providedParams);

        $this->assertNull($result);
    }

    #[Test]
    public function returnsNullForNoType(): void
    {
        $fn = fn($mixed) => null;
        $reflection = new ReflectionFunction($fn);
        $mixedParam = $reflection->getParameters()[0];

        $providedParams = ['value' => new \DateTime()];

        $result = $this->resolver->resolve($mixedParam, $providedParams);

        $this->assertNull($result);
    }

    #[Test]
    public function handlesUnionTypesWithInstanceof(): void
    {
        $fn = fn(\DateTime|\DateTimeImmutable $date) => null;
        $reflection = new ReflectionFunction($fn);
        $dateParam = $reflection->getParameters()[0];

        $dateValue = new \DateTimeImmutable();

        $providedParams = ['some_value' => $dateValue, 'other' => 'string'];

        $result = $this->resolver->resolve($dateParam, $providedParams);

        $this->assertEquals([0, $dateValue], $result);
    }
}
