<?php

namespace Bermuda\DI\Parameter;

/**
 * Interface ParameterResolverInterface
 *
 * This interface defines the contract for classes that resolve the value of a method or function parameter.
 *
 * The resolve method takes a ReflectionParameter, along with:
 * - An array of provided parameters (typically user-provided values).
 * - An array of parameters that have already been resolved.
 *
 * The method attempts to determine an appropriate value for the parameter. If successful, it returns a
 * two-element array where:
 *   - Index 0 is the position of the parameter in the method/function signature.
 *   - Index 1 is the resolved value (which can be of any type).
 *
 * If the parameter cannot be resolved, the method should return null.
 *
 * Implementations of this interface may throw a ParameterResolutionExceptionInterface if an error occurs
 * during the resolution process.
 */
interface ParameterResolverInterface
{
    /**
     * Resolves the value for a given method or function parameter.
     *
     * @param \ReflectionParameter $parameter The reflection information for the parameter to resolve.
     * @param array $providedParameters An array of parameters explicitly provided for resolution.
     * @param array $resolvedParameters An array of parameters that have been resolved so far.
     *
     * @return null|array{0: int, 1: mixed} Returns a two-element array with the parameter's position and its resolved value,
     *                                      or null if the parameter could not be resolved.
     * @throws ParameterResolutionExceptionInterface If an error occurs during the resolution process.
     */
    public function resolve(\ReflectionParameter $parameter, array $providedParameters = [], array $resolvedParameters = []): ?array;
}

