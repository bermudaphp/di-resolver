<?php

namespace Bermuda\ParameterResolver;

interface ParameterResolutionExceptionInterface extends \Throwable
{
    public \ReflectionParameter $parameter {
        get;
    }

    public array $providedParameters {
        get;
    }

    public array $resolvedParameters {
        get;
    }
}