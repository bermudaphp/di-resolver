<?php

namespace Bermuda\ParameterResolver;

/**
 * DefaultValueResolver is responsible for resolving the default value of a parameter.
 *
 * It implements the ParameterResolverInterface and attempts to resolve a parameter's value
 * by checking if that parameter has a default value available. If a default value exists,
 * it returns an array with the parameter's position and its default value; otherwise, it returns null.
 */
final class DefaultValueResolver implements ParameterResolverInterface
{
    /**
     * Resolves the default value for the given ReflectionParameter.
     *
     * This method checks if the provided parameter has a default value available.
     * - If so, it returns an array with:
     *   - The parameter's position in the function/method signature at key "0".
     *   - The default value of the parameter at key "1".
     * - If not, it returns null.
     *
     * @param \ReflectionParameter $parameter            The reflection of the parameter to resolve.
     * @param array                $providedParameters   An array of parameters explicitly provided for resolution.
     * @param array                $resolvedParameters     An array of parameters that have already been resolved.
     *
     * @return array{0: int, 1: mixed}|null Returns a two-element array with the parameter's position and default value,
     *                    or null if the parameter does not have a default value.
     */
    public function resolve(\ReflectionParameter $parameter, array $providedParameters = [], array $resolvedParameters = []): ?array
    {
        return $parameter->isDefaultValueAvailable()
            ? [$parameter->getPosition(), $parameter->getDefaultValue()]
            : null;
    }
}