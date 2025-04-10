<?php

namespace Bermuda\ParameterResolver\Resolver;

use Bermuda\CheckType\Type;
use ReflectionParameter;

class ResolverException extends \RuntimeException
{
    public static function createFromPrev(\Throwable $previous): ResolverException
    {
        return new self($previous->getMessage(), $previous->getCode(), $previous);
    }

    public static function createFromParameter(ReflectionParameter $parameter): ResolverException
    {
        $self = new self('Can\'t resolve parameter: $' . $parameter->getName());
        $self->line = $parameter->getDeclaringFunction()->getStartLine();
        $self->file = $parameter->getDeclaringFunction()->getFileName();

        return $self;
    }

    public static function createForParameterType(\ReflectionParameter $parameter, mixed $entry): ResolverException
    {
        $self = new self('Argument #'.$parameter->getPosition().' ($'.$parameter->getName().') must be of type ' . $parameter->getType() . ', given ' . Type::gettype($entry, Type::objectAsClass));
        $self->line = $parameter->getDeclaringFunction()->getStartLine();
        $self->file = $parameter->getDeclaringFunction()->getFileName();

        return $self;
    }
}
