<?php

namespace Bermuda\DI\Parameter\ParameterResolver;

use Bermuda\Reflection\TypeMatcher;
use IteratorAggregate;
use ReflectionParameter;
use Psr\Container\ContainerInterface;

/**
 * ParameterResolver aggregates multiple implementations of ParameterResolverInterface and
 * iterates over them to resolve the values for method or function parameters.
 *
 * This class implements both the Countable and IteratorAggregate interfaces, allowing
 * easy management and traversal of registered resolvers.
 */
final class ParameterResolver implements \Countable, IteratorAggregate
{
    /**
     * @var ParameterResolverInterface[] List of registered parameter resolvers.
     */
    private array $resolvers = [];

    /**
     * Constructor.
     *
     * @param iterable $resolvers An iterable of instances that implement ParameterResolverInterface.
     */
    public function __construct(iterable $resolvers = [])
    {
        foreach ($resolvers as $resolver) $this->addResolver($resolver);
    }

    /**
     * Adds a new parameter resolver to the collection.
     *
     * @param ParameterResolverInterface $resolver The resolver to add.
     * @param bool $prepend If set to true, the resolver is added to the beginning of the list.
     */
    public function addResolver(ParameterResolverInterface $resolver, bool $prepend = false): void
    {
        if ($prepend) array_unshift($this->resolvers, $resolver);
        else $this->resolvers[] = $resolver;
    }

    /**
     * Checks whether a specific resolver is already registered.
     *
     * @template T of ParameterResolverInterface
     *
     * @param ParameterResolverInterface|class-string<T> $resolver An instance or class name of a resolver.
     * @return bool True if the resolver exists; false otherwise.
     */
    public function hasResolver(ParameterResolverInterface|string $resolver): bool
    {
        if ($resolver instanceof ReflectionParameter) $resolver = $resolver::class;
        foreach ($this->resolvers as $r) {
            if ($r::class == $resolver) return true;
        }

        return false;
    }

    /**
     * Resolves parameter values for an array of ReflectionParameters.
     *
     * This method iterates over each ReflectionParameter and attempts to resolve its value via
     * the registered resolvers. The result is an array where keys represent the parameter positions
     * and values are the resolved values.
     *
     * @param ReflectionParameter[] $parameters         An array of ReflectionParameter instances.
     * @param array<string, mixed>  $providedParameters   An associative array of explicitly provided parameters.
     *
     * @return array<int, mixed> Returns an array of resolved parameter values keyed by the parameter's position.
     *
     * @throws ParameterResolutionExceptionInterface If any of the parameters cannot be resolved.
     */
    public function resolve(array $parameters, array $providedParameters = []): array
    {
        $resolved = [];
        foreach ($parameters as $parameter) {
            $pair = $this->resolveParameter($parameter, $providedParameters, $resolved);
            if ($pair) $resolved[$pair[0]] = $pair[1];
        }

        return $resolved;
    }

    /**
     * Resolves an individual ReflectionParameter to a value.
     *
     * Iterates through all registered resolvers (obtained using getIterator()) and returns the first successful resolution.
     * The returned array is formatted as:
     *   [0 => parameter position, 1 => resolved value]
     *
     * If a parameter's declared type exists, the method checks that the resolved value matches the expected type.
     * If it does not, a ParameterResolutionException is thrown.
     *
     * @param ReflectionParameter $parameter         The parameter to resolve.
     * @param array               $providedParameters  An array of explicitly provided parameters.
     * @param array               $resolvedParameters  An array of parameters that have been resolved so far.
     *
     * @return array{0: int, 1: mixed} Returns a two-element array: the parameter's position and its resolved value.
     *
     * @throws ParameterResolutionExceptionInterface If no registered resolver is able to resolve the parameter
     *                                               or if the resolved value does not match the parameter's type.
     */
    public function resolveParameter(ReflectionParameter $parameter, array $providedParameters = [], array $resolvedParameters = []): array
    {
        foreach ($this->resolvers as $resolver) {
            $pair = $resolver->resolve($parameter, $providedParameters);
            if ($pair) {
                if ($parameter->getType() && !TypeMatcher::match($parameter->getType(), $pair[1])) {
                    throw ParameterResolutionException::createForTypeMismatch($parameter, $providedParameters, $resolvedParameters, $pair[1]);
                }

                return $pair;
            }
        }

        throw throw ParameterResolutionException::create($parameter, $providedParameters, $resolvedParameters);
    }

    /**
     * Returns the count of registered resolvers.
     *
     * @return int The number of resolvers registered.
     */
    public function count(): int
    {
        return count($this->resolvers);
    }


    /**
     * Provides an iterator to traverse the registered resolvers.
     *
     * @return \Generator<ParameterResolverInterface> A generator that yields each registered resolver.
     */
    public function getIterator(): \Generator
    {
        return yield from $this->resolvers;
    }

    /**
     * Creates a default ParameterResolver instance with a set of standard resolvers.
     *
     * The default resolvers include:
     * - ArrayResolver
     * - ArrayTypedResolver
     * - ContainerResolver configured with the provided container
     * - DefaultValueResolver
     * - NullableResolver
     *
     * @param ContainerInterface $container A PSR-11 container instance.
     *
     * @return self A new instance of ParameterResolver with default resolvers.
     */
    public static function createDefaults(ContainerInterface $container): self
    {
        return new self([
            new ArrayResolver,
            new ArrayTypedResolver,
            new ContainerResolver($container),
            new DefaultValueResolver,
            new NullableResolver
        ]);
    }
}
