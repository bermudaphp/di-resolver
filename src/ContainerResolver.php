<?php

namespace Bermuda\ParameterResolver;

use Bermuda\Reflection\Reflection;
use ReflectionParameter;
use Bermuda\DI\Attribute\Config;
use Bermuda\DI\Attribute\Inject;
use Psr\Container\ContainerInterface;
use ReflectionType;

/**
 * ContainerResolver resolves a parameter's value using a PSR-11 container.
 *
 * This class implements the ParameterResolverInterface and provides a strategy for resolving
 * parameters based on one of the following, in order:
 *
 * 1. A Config attribute attached to the parameter – in which a configuration value is extracted,
 *    based on a defined path, from the container.
 * 2. An Inject attribute – in which a service is retrieved from the container using a service id.
 * 3. The parameter's type declaration – if the declared type is a class and the container has an entry
 *    for that class, it returns the corresponding service.
 *
 * The resolution outcome is returned as a two-element array: key "0" is the parameter's position,
 * and key "1" holds the resolved value.
 */
final class ContainerResolver implements ParameterResolverInterface
{
    /**
     * Constructor.
     *
     * @param ContainerInterface $container A PSR-11 container used to resolve dependencies.
     */
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    /**
     * Attempts to resolve the value for a given ReflectionParameter.
     *
     * The resolution process is carried out in the following order:
     *   - First, if a Config attribute is detected, resolve the value from container configuration.
     *   - Next, if an Inject attribute is detected, resolve by fetching the service from the container.
     *   - Lastly, try to resolve based on the parameter's declared type.
     *
     * @param ReflectionParameter $parameter The parameter to resolve.
     * @param array $providedParameters An array of explicitly provided parameters (unused here).
     * @param array $resolvedParameters An array of parameters that have been resolved so far (unused here).
     *
     * @return null|array{0: int, 1: mixed} Returns an array [parameter position, resolved value] or null if unresolved.
     *
     * @throws ParameterResolutionException If a configuration key is undefined or its value is not accessible.
     */
    public function resolve(\ReflectionParameter $parameter, array $providedParameters = [], array $resolvedParameters = []): ?array
    {
        if ($entry = $this->resolveFromConfigAttribute($parameter, $providedParameters, $resolvedParameters)) {
            return [$parameter->getPosition(), $entry];
        }

        if ($entry = $this->resolveFromInjectAttribute($parameter)) {
            return [$parameter->getPosition(), $entry];
        }

        return $this->resolveFromType($parameter);
    }

    /**
     * Resolves a parameter value using the Inject attribute.
     *
     * This method inspects the parameter for an Inject attribute. If found,
     * it uses the attribute's id to retrieve the corresponding service from the container.
     *
     * @param ReflectionParameter $parameter The parameter to inspect.
     *
     * @return mixed Resolved service instance if available; otherwise, null.
     */
    private function resolveFromInjectAttribute(ReflectionParameter $parameter): mixed
    {
        $entryId = Reflection::getFirstMetadata($parameter, Inject::class)?->id;
        return $entryId !== null ? $this->container->get($entryId) : null;
    }

    /**
     * Resolves a parameter value based on its declared type.
     *
     * If the parameter has a union type, this method iterates through each member type
     * and attempts to resolve it. Otherwise, it directly resolves using the declared type.
     *
     * @param ReflectionParameter $parameter The parameter to resolve.
     *
     * @return null|array Returns a two-element array [position, resolved value] or null if resolution fails.
     */
    private function resolveFromType(ReflectionParameter $parameter): ?array
    {
        if (($type = $parameter->getType()) instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $t) {
                $pair = $this->doResolveFromType($parameter, $t);
                if ($pair) return $pair;
            }
        }

        return $this->doResolveFromType($parameter, $type);
    }

    /**
     * Attempts to resolve the parameter value for a specific type.
     *
     * Uses a match expression to evaluate resolution:
     * - If the type is not a named type or is a built-in type (like int, string), resolve fails.
     * - If the container has an entry for the type's class name, return the service.
     *
     * @param ReflectionParameter $parameter The parameter in question.
     * @param ReflectionType      $type      The specific ReflectionType to resolve.
     *
     * @return null|array Returns a two-element array [position, resolved value] or null if resolution is unsuccessful.
     */
    private function doResolveFromType(ReflectionParameter $parameter, ?ReflectionType $type): ?array
    {
        return match (true) {
            !$type instanceof \ReflectionNamedType || $type->isBuiltin() => null,
            $this->container->has($type->getName()) => [$parameter->getPosition(), $this->container->get($type->getName())],
            default => null,
        };
    }

    /**
     * Resolves a parameter's value using a Config attribute.
     *
     * This method retrieves the Config attribute attached to the parameter and uses it to extract a configuration
     * value from the container. The extraction follows a specified path (which can be split into multiple keys
     * if the 'explodeDots' property is enabled). If any key is missing or the value is inaccessible, an exception is thrown.
     *
     * @param ReflectionParameter $parameter          The parameter to resolve.
     * @param array               $providedParameters An array of explicitly provided parameters.
     * @param array               $resolvedParameters An array of already resolved parameters.
     *
     * @return mixed Returns the extracted configuration value.
     *
     * @throws ParameterResolutionException If a configuration key is undefined or its value is not accessible.
     */
    private function resolveFromConfigAttribute(ReflectionParameter $parameter, array $providedParameters, array $resolvedParameters): mixed
    {
        $config = Reflection::getFirstMetadata($parameter, Config::class);
        if (!$config) return null;

        try {
            $entry = $this->container->get(Config::$key);
            return $config->getEntryFromConfig($entry);
        } catch (\Throwable $ex) {
            throw ParameterResolutionException::createFromPrev($parameter, $providedParameters, $resolvedParameters, $ex);
        }
    }

    /**
     * Creates a new instance of ContainerResolver using the provided container.
     *
     * @param ContainerInterface $container A PSR-11 container instance.
     *
     * @return ContainerResolver A new instance of ContainerResolver.
     */
    public static function createFromContainer(ContainerInterface $container): ContainerResolver
    {
        return new self($container);
    }
}
