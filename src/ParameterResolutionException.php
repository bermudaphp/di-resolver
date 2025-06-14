<?php

namespace Bermuda\ParameterResolver;

use Throwable;
use RuntimeException;
use Bermuda\CheckType\Type;
use ReflectionParameter;

/**
 * ParameterResolutionException is thrown when an error occurs during the resolution of a parameter.
 *
 * This exception encapsulates the ReflectionParameter (the parameter that failed to resolve),
 * as well as the arrays of provided and already resolved parameters. It extends \RuntimeException
 * and implements the ParameterResolutionExceptionInterface.
 */
class ParameterResolutionException extends RuntimeException implements ParameterResolutionExceptionInterface
{
    /**
     * Constructor.
     *
     * @param ReflectionParameter $parameter          The reflection information of the parameter that failed to resolve.
     * @param array               $providedParameters An array of parameters provided for resolution.
     * @param array               $resolvedParameters An array of parameters already resolved.
     * @param string              $message            The error message.
     * @param ?Throwable          $previous           The previous exception that triggered this error, if any.
     */
    public function __construct(
        public readonly ReflectionParameter $parameter,
        public readonly array $providedParameters,
        public readonly array $resolvedParameters,
        string $message = "",
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Creates a new ParameterResolutionException instance based on a previous Throwable.
     *
     * This factory method wraps an existing exception in a new ParameterResolutionException,
     * providing additional context regarding the parameter resolution process.
     *
     * @param ReflectionParameter $parameter          The reflection of the parameter that triggered the error.
     * @param array               $providedParameters An array of parameters provided for resolution.
     * @param array               $resolvedParameters An array of parameters already resolved.
     * @param Throwable           $previous           The previous exception that caused this error.
     *
     * @return static Returns a new instance of ParameterResolutionException.
     */
    public static function createFromPrev(
        ReflectionParameter $parameter,
        array $providedParameters,
        array $resolvedParameters,
        Throwable $previous
    ): self
    {
        $self = new self(
            $parameter,
            $providedParameters,
            $resolvedParameters,
            'An error occurred during parameter resolving: ' . $previous->getMessage(),
            $previous
        );

        $self->code = $previous->getCode();
        return $self;
    }

    /**
     * Creates a new exception instance when a parameter cannot be resolved.
     *
     * This method constructs a detailed error message that includes:
     * - The 1-based position of the parameter.
     * - The parameter's name.
     * - The declaring class and function.
     *
     * It also sets the error's file and line properties (based on the start of the declaring function)
     * to provide helpful debugging information.
     *
     * @param ReflectionParameter $parameter          The reflection of the parameter that could not be resolved.
     * @param array               $providedParameters An array of parameters provided for resolution.
     * @param array               $resolvedParameters An array of parameters already resolved.
     *
     * @return static Returns a new instance of ParameterResolutionException.
     */
    public static function create(
        ReflectionParameter $parameter,
        array $providedParameters,
        array $resolvedParameters
    ): self
    {
        $msg = sprintf('Can\'t resolve parameter: #%s ($%s) for %s::%s()',
            $parameter->getPosition() + 1,
            $parameter->getName(),
            $parameter->getDeclaringClass()->getName(),
            $parameter->getDeclaringFunction()->getName()
        );

        $self = new self($parameter, $providedParameters, $resolvedParameters, $msg);

        $self->line = $parameter->getDeclaringFunction()->getStartLine();
        $self->file = $parameter->getDeclaringFunction()->getFileName();

        return $self;
    }

    /**
     * Creates a new exception instance when the resolved parameter's type does not match the expected type.
     *
     * This method generates an error message detailing:
     * - The parameter's position and name.
     * - The expected type (as declared).
     * - The actual type of the provided value.
     *
     * It also assigns the file and line properties based on the start of the declaring function to aid debugging.
     *
     * @param ReflectionParameter $parameter          The reflection of the parameter with a type mismatch.
     * @param array               $providedParameters An array of parameters provided for resolution.
     * @param array               $resolvedParameters An array of parameters already resolved.
     * @param mixed               $entry              The value that does not match the expected type.
     *
     * @return static Returns a new instance of ParameterResolutionException.
     */
    public static function createForTypeMismatch(
        ReflectionParameter $parameter,
        array $providedParameters,
        array $resolvedParameters,
        mixed $entry
    ): self
    {
        $self = new self(
            $parameter,
            $providedParameters,
            $resolvedParameters,
            'Argument #'.$parameter->getPosition().' ($'.$parameter->getName().') must be of type ' . $parameter->getType() . ', given ' . Type::gettype($entry, Type::FLAG_OBJECT_AS_CLASS)
        );

        $self->line = $parameter->getDeclaringFunction()->getStartLine();
        $self->file = $parameter->getDeclaringFunction()->getFileName();

        return $self;
    }
}
