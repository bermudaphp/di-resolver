<?php

namespace Bermuda\DI\Parameter;

/**
 * Interface ParameterResolutionExceptionInterface
 *
 * This interface must be implemented by exceptions that occur during parameter resolution.
 * It enforces that the exception exposes key contextual information through the following read-only properties:
 *
 * - **$parameter**: A ReflectionParameter instance representing the parameter that failed to resolve.
 * - **$providedParameters**: An array of parameters that were provided for attempted resolution.
 * - **$resolvedParameters**: An array of parameters that had already been resolved when the error occurred.
 *
 * Implementations of this interface allow consumers to gain insight into the state of parameter resolution
 * at the time the exception was thrown.
 */
interface ParameterResolutionExceptionInterface extends \Throwable
{
    // Returns the ReflectionParameter that could not be resolved.
    public ReflectionParameter $parameter {
        get;
    }

    // Returns an array of the parameters that were provided for resolution.
    public array $providedParameters {
        get;
    }

    // Returns an array of the parameters that had already been resolved.
    public array $resolvedParameters {
        get;
    }
}
