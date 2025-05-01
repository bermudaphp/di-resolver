<?php

namespace Bermuda\DI\Parameter;

/**
 * NullableResolver is a parameter resolver that handles parameters which allow null values.
 *
 * When a function or method parameter is declared to allow null (using a nullable type),
 * this resolver will automatically resolve that parameter with a null value. This avoids
 * further resolution attempts when null is an acceptable value.
 *
 * It implements the ParameterResolverInterface and returns a two-element array with the parameter's
 * position and a null value if the parameter allows null, otherwise it returns null.
 */
final class NullableResolver implements ParameterResolverInterface
{
    /**
     * Resolves the value for a parameter that allows null.
     *
     * The method checks whether the given ReflectionParameter allows a null value. If true,
     * it returns an array where key "0" represents the parameter's position in the function/method
     * signature and key "1" is the null value. This indicates that the parameter should be set to null.
     * If the parameter does not allow null, the method returns null, indicating that resolution does not occur here.
     *
     * @param \ReflectionParameter $parameter           The parameter to be resolved.
     * @param array                $providedParameters  An array of parameters explicitly provided (unused here).
     * @param array                $resolvedParameters    An array of parameters that have already been resolved (unused here).
     *
     * @return array{0: int, 1: null}|null Returns a two-element array with the parameter position and null if the parameter allows null,
     *                    or null if it does not.
     */
    public function resolve(\ReflectionParameter $parameter, array $providedParameters = [], array $resolvedParameters = []): ?array
    {
        if ($parameter->allowsNull()) return [$parameter->getPosition(), null];
        return null;
    }
}
