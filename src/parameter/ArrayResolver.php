<?php

namespace Bermuda\ParameterResolver;

/**
 * Class ArrayResolver
 *
 * This parameter resolver attempts to resolve a function or method parameter by searching for a matching key
 * in an array of provided parameters. It first checks for a match based on the parameter's name.
 * If no match is found by name, it then checks based on the parameter's positional index (zero-based).
 *
 * If a matching parameter is found, the resolver returns an array where key "0" is the parameter's position and
 * key "1" is the corresponding value. If no match is found, the resolver returns null.
 */
final class ArrayResolver implements ParameterResolverInterface
{
    /**
     * Attempts to resolve the provided ReflectionParameter using the values from the provided parameters array.
     *
     * The method operates in the following steps:
     *   1. Retrieve the parameter's position and name.
     *   2. Check if the provided array contains a key matching the parameter name.
     *      - If so, return an array with the parameter's position and its associated value.
     *   3. If not found under the name, check if the array contains a key matching the parameter's position.
     *      - If so, return an array with the parameter's position and its associated value.
     *   4. If neither lookup is successful, return null.
     *
     * @param \ReflectionParameter $parameter           The parameter that needs to be resolved.
     * @param array                $providedParameters  An array of provided parameters used for resolution.
     * @param array                $resolvedParameters    An array of parameters that have already been resolved (not used in this resolver).
     *
     * @return array{0: int, 1: mixed}|null Returns a two-element array [position, value] if the parameter is found,
     *                    or null if the parameter cannot be resolved from the provided array.
     */
    public function resolve(\ReflectionParameter $parameter, array $providedParameters = [], array $resolvedParameters = []): ?array
    {
        $position = $parameter->getPosition();

        if (isset($providedParameters[$name = $parameter->getName()]) || array_key_exists($name, $providedParameters)) {
            return [$position, $providedParameters[$parameter->getName()]];
        } elseif (isset($providedParameters[$position]) || array_key_exists($position, $providedParameters)) {
            return [$position, $providedParameters[$position]];
        }

        return null;
    }
}
