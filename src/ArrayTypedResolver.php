<?php

namespace Bermuda\ParameterResolver;

use ReflectionParameter;

/**
 * ArrayTypedResolver attempts to resolve a parameter's value from an array based on its declared type.
 *
 * This resolver inspects the type-hint of a given ReflectionParameter and searches for a
 * matching value in the provided parameters array. It first checks if the array has an element
 * keyed by the type name. If none is found, it iterates over all values in the parameters array to
 * see if any value is an instance of the required type.
 *
 * If a matching value is found, the method returns a two-element array where:
 *   - key "0" is the parameter's position in the signature,
 *   - key "1" is the matched value.
 *
 * If no match is found, the resolver returns null.
 *
 * This mechanism enables automatic binding of dependencies using type information from a flexible parameter array.
 */
final class ArrayTypedResolver implements ParameterResolverInterface
{
    /**
     * Attempts to resolve the given parameter using the provided parameters array by matching its type.
     *
     * First, it retrieves the parameter's declared type and its position. If the parameter uses a union type,
     * the resolver iterates over each possible type and attempts to doResolve() for each. The first successful
     * resolution result is returned. For single types, it calls doResolve() directly.
     *
     * @param ReflectionParameter $parameter           The parameter to resolve.
     * @param array               $providedParameters  An associative or indexed array of provided parameters.
     * @param array               $resolvedParameters    An array of parameters that have already been resolved (not used here).
     *
     * @return array{0:int, 1: mixed}|null Returns an array [position, resolved value] if a match is found, or null otherwise.
     */
    public function resolve(\ReflectionParameter $parameter, array $providedParameters = [], array $resolvedParameters = []): ?array
    {
        $type = $parameter->getType();
        $position = $parameter->getPosition();

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $t) {
                $pair = $this->doResolve($t, $position, $providedParameters);
                if ($pair !== null) return $pair;
            }
        }

        return $this->doResolve($type, $position, $providedParameters);
    }

    /**
     * Performs the resolution process for a specific ReflectionType.
     *
     * This method checks if the provided type is a valid, non-builtin named type.
     * It then attempts the resolution in two steps:
     *
     *  1. Checks for an exact match in the provided parameters array using the type's name as the key.
     *  2. If no such key exists, it searches the array for any value that is an instance
     *     of the declared type.
     *
     * @param ReflectionType|null $type                The declared type of the parameter.
     * @param int                 $position            The position of the parameter in the signature.
     * @param array               $providedParameters  An array of parameters to search in.
     *
     * @return array|null Returns an array [position, resolved value] if successful; otherwise, null.
     */
    private function doResolve(?\ReflectionType $type, int $position, array $providedParameters): ?array
    {
        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        if (isset($providedParameters[$typeName = $type->getName()])) {
            return [$position, $providedParameters[$typeName]];
        }

        $found = array_find($providedParameters, static fn (mixed $parameter) => $parameter instanceof $typeName);

        return $found !== null ? [$position, $found] : null;
    }
}
