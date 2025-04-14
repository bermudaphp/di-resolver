<?php

namespace Bermuda\ParameterResolver;

use ReflectionParameter;
use Bermuda\CheckType\Type;

class ResolverException extends \RuntimeException
{
    public static function createFromPrev(\Throwable $previous): ResolverException
    {
        return new self($previous->getMessage(), $previous->getCode(), $previous);
    }

    public static function createFromParameter(ReflectionParameter $parameter): ResolverException
    {
        $msg = sprintf('Can\'t resolve parameter: #%s ($%s) for %s::%s()',
            $parameter->getPosition() + 1,
            $parameter->getName(),
            $parameter->getDeclaringClass()->getName(),
            $parameter->getDeclaringFunction()->getName()
        );

        $self = new self($msg);
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
