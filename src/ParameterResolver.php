<?php

namespace Bermuda\ParameterResolver\Resolver;

use Psr\Container\ContainerInterface;
use ReflectionParameter;
use Psr\Http\Message\ServerRequestInterface;
use function Bermuda\Config\conf;

final class ParameterResolver implements ParameterResolverInterface
{
    /**
     * @var ParametrResolverInterface[]
     */
    private array $resolvers;

    public function __construct(iterable $resolvers = [])
    {
        foreach ($resolvers as $resolver) $this->addResolver($resolver);
    }

    public function addResolver(ParameterResolverInterface $resolver, bool $prepend = false): void
    {
        $prepend ? array_unshift($this->resolvers, $resolver) : $this->resolvers[] = $resolver;
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

    public static function createFromContainer(ContainerInterface $container): self
    {
        $resolver = new self();
        foreach (conf($container)->get(ConfigProvider::CONFIG_KEY_RESOLVERS, []) as $resolver) {
            $resolver->resolve($container->get($resolver));
        }

        $resolver->resolve(new ContainerResolver($container));

        return $resolver;
    }
}
