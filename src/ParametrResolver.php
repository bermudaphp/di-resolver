<?php

namespace Bermuda\ParameterResolver\Resolver;

use ReflectionParameter;
use Psr\Http\Message\ServerRequestInterface;

final class ParameterResolver implements ParametrResolverInterface
{
    /**
     * @var ParametrResolverInterface[]
     */
    private array $resolvers;

    public function __construct(iterable $resolvers = [])
    {
        foreach ($resolvers as $resolver) $this->addResolver($resolver);
    }

    public function addResolver(ParameterResolverInterface $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(ReflectionParameter $parameter, array $params = []): array
    {
        foreach ($this->resolvers as $resolver) {
            $pair = $resolver->resolve($parameter, $params);
            if ($pair !== null) return $pair;
        }

        if (array_key_exists($parameter->getName(), $params)) {
            return $params[$parameter->getName()];
        }

        if ($parameter->isDefaultValueAvailable()) {
            return [$parameter->getName() => $parameter->getDefaultValue()];
        }

        if ($parameter->allowsNull()) return [$parameter->getName(), null];

        throw ResolverException::createFromParameter($parameter);
    }
}
