<?php

namespace Bermuda\ParameterResolver\Resolver;

use ReflectionParameter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

use function Bermuda\Config\conf;

final class ParameterResolver
{
    /**
     * @var ParameterResolverInterface[]
     */
    private array $resolvers;

    public function __construct(iterable $resolvers = [])
    {
        foreach ($resolvers as $resolver) $this->addResolver($resolver);
    }

    /**
     * @param ParameterResolverInterface $resolver
     * If $prepend true, then add the resolver to the beginning of the queue
     * @param bool $prepend
     * @return void
     */
    public function addResolver(ParameterResolverInterface $resolver, bool $prepend = false): void
    {
        $prepend ? array_unshift($this->resolvers, $resolver) : $this->resolvers[] = $resolver;
    }

    /**
     * @param \ReflectionParameter $parameters
     * @param array<string, $params> $params
     *  The method returns an array of function parameters,
     *  where the keys will be the names of parameters
     * @return array<string, mixed>
     * An exception will be thrown if the parameter cannot be resolved
     * @throws ResolverException
     */
    public function resolve(array $parameters, array $params = []): array
    {
        $resolved = [];
        foreach ($this->parameters as $parameter) {
            $pair = $this->resolveParameter($parameter, $params);
            $resolved[$pair[0]] = $pair[1];
        }

        return $resolved;
    }

    /**
     * @param ReflectionParameter $parameter
     * @param array $params
     * The method returns an array where the key “0” will be the name of the parameter
     * and the key “1” will be its value
     * @return array{0: string, 1: mixed}
     * An exception will be thrown if the parameter cannot be resolved
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
        $self = new self();
        foreach (conf($container)->get(ConfigProvider::CONFIG_KEY_RESOLVERS, []) as $resolver) {
            $self->addResolver($container->get($resolver));
        }

        $self->addResolver(new ContainerResolver($container));
        return $self;
    }
}
