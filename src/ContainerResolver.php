<?php

namespace Bermuda\ParameterResolver;

use ReflectionParameter;
use Bermuda\Di\Attribute\Config;
use Bermuda\Di\Attribute\Container;
use Psr\Container\ContainerInterface;

final class ContainerResolver implements ParameterResolverInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function resolve(ReflectionParameter $parameter, array $params = []): ?array
    {
        if ($entry = $this->resolveFromConfig($parameter)) {
            return [$parameter->getName(), $entry];
        }

        if ($entry = $this->resolveFromContainerAttribute($parameter)) {
            return [$parameter->getName(), $entry];
        }

        return $this->resolveFromType($parameter);
    }

    private function resolveFromContainerAttribute(ReflectionParameter $parameter): mixed
    {
        $attribute = $this->getAttribute($parameter, Container::class);
        return $attribute ? $this->container->get($attribute->newInstance()->id) : null;
    }

    private function resolveFromType(ReflectionParameter $parameter): ?array
    {
        $type = $parameter->getType();
        $namedTypes = ($type instanceof \ReflectionUnionType)
            ? array_filter($type->getTypes(), fn($t) => $t instanceof \ReflectionNamedType)
            : [$type];

        foreach ($namedTypes as $namedType) {
            if ($this->container->has($namedType->getName())) {
                return [$parameter->getName(), $this->container->get($namedType->getName())];
            }
        }

        return null;
    }

    private function resolveFromConfig(ReflectionParameter $parameter): mixed
    {
        $config = $this->getAttribute($parameter, Config::class);
        if (!$config) return null;

        /**
         * @var Config $config
         */
        $config = $config->newInstance();
        $entry = $this->container->get(Config::$key);
        $path = $config->explodeDots ? explode('.', $config->path) : [$config->path];

        $keysTraversed = [];

        foreach ($path as $key) {
            $keysTraversed[] = $key;

            if (is_array($entry) || $entry instanceof \ArrayAccess) {
                if (!isset($entry[$key])) {
                    $pathString = implode(' → ', $keysTraversed);
                    throw new ResolverException("Undefined config key: $pathString");
                }
                $entry = $entry[$key];
            } else {
                $pathString = implode(' → ', $keysTraversed);
                throw new ResolverException("Config value at path '$pathString' is not accessible");
            }
        }

        return $entry;
    }

    private function getAttribute(ReflectionParameter $parameter, string $cls):? \ReflectionAttribute
    {
        return $parameter->getAttributes($cls)[0] ?? null;
    }

    public static function createFromContainer(ContainerInterface $container): ContainerResolver
    {
        return new self($container);
    }
}
