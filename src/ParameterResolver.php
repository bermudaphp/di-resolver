<?php

namespace Bermuda\ParameterResolver;

use ReflectionParameter;
use Psr\Container\ContainerInterface;

use function Bermuda\Config\conf;

final class ParameterResolver
{
    private(set) ResolverCollector $resolvers;

    public function __construct(iterable $resolvers = [])
    {
        $this->resolvers = new ResolverCollector($resolvers);
    }

    /**
     * @param \ReflectionParameter $parameters
     * @param array<string, $params> $params
     *  The method returns an array of function parameters,
     *  where the keys will be the names of parameters
     * @return array<string, mixed>
     */
    public function resolve(array $parameters, array $params = []): array
    {
        $resolved = [];
        foreach ($parameters as $parameter) {
            $pair = $this->resolveParameter($parameter, $params);
            if ($pair) $resolved[$pair[0]] = $pair[1];
        }

        return $resolved;
    }

    /**
     * @param ReflectionParameter $parameter
     * @param array $params
     * The method returns an array where the key “0” will be the name of the parameter
     * and the key “1” will be its value
     * @return array{0: string, 1: mixed}
     * @throws ResolverException
     */
    public function resolveParameter(ReflectionParameter $parameter, array $params = []): array
    {
        foreach ($this->resolvers as $resolver) {
            $pair = $resolver->resolve($parameter, $params);
            if ($pair) return $pair;
        }

        if (array_key_exists($parameter->getName(), $params)) {
            return [$parameter->getName() => $params[$parameter->getName()]];
        }

        if ($parameter->isDefaultValueAvailable()) {
            return [$parameter->getName() => $parameter->getDefaultValue()];
        }

        if ($parameter->allowsNull()) return [$parameter->getName(), null];

        throw ResolverException::createFromParameter($parameter);
    }

    public static function createFromContainer(ContainerInterface $container): self
    {
        return self::createFromCollector($container->get(ResolverCollector::class));
    }

    public static function createFromCollector(ResolverCollector $collector): self
    {
        $self = new self();
        $self->resolvers = $collector;

        return $self;
    }
}
