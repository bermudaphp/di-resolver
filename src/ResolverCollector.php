<?php

namespace Bermuda\ParameterResolver;

use Traversable;
use Psr\Container\ContainerInterface;

use function Bermuda\Config\conf;

final class ResolverCollector implements \IteratorAggregate
{
    /**
     * @var ParameterResolverInterface
     */
    private(set) array $resolvers = [];

    /**
     * @param iterable<ParameterResolverInterface> $resolvers
     */
    public function __construct(iterable $resolvers)
    {
        foreach ($resolvers as $resolver) $this->addResolver($resolver);
    }

    public function __clone(): void
    {
        foreach ($this->resolvers as $i => $resolver) $this->resolvers[$i] = clone $resolver;
    }

    /**
     * @param ParameterResolverInterface $resolver
     * If $prepend true, then add the resolver to the beginning of the queue
     * @param bool $prepend
     * @return self
     */
    public function withResolver(ParameterResolverInterface $resolver, bool $prepend = false): self
    {

        $copy = clone $this;
        $prepend ? array_unshift($this->resolvers, $resolver) : $this->resolvers[] = $resolver;

        return $copy;
    }

    /**
     * @param iterable<ParameterResolverInterface> $resolvers
     * If $prepend true, then add the resolver to the beginning of the queue
     * @param bool $prepend
     * @return self
     */
    public function withResolvers(iterable $resolvers, bool $prepend = false): self
    {
        $copy = clone $this;

        if ($prepend) $resolvers = array_reverse($resolvers);

        foreach ($resolvers as $resolver) $copy->addResolver($resolver, $prepend);

        return $copy;
    }

    /**
     * @return \Generator<ParameterResolverInterface>
     */
    public function getIterator(): Traversable
    {
        yield from $this->resolvers;
    }

    private function addResolver(ParameterResolverInterface $resolver, bool $prepend = false): void
    {
        $prepend ? array_unshift($this->resolvers, $resolver) : $this->resolvers[] = $resolver;
    }

    public static function createFromContainer(ContainerInterface $container): self
    {
        $self = new self([]);
        foreach (conf($container)->get(ConfigProvider::CONFIG_KEY_RESOLVERS, []) as $resolver) {
            $self->addResolver($container->get($resolver));
        }

        $self->addResolver(new ContainerResolver($container));
        return $self;
    }
}
