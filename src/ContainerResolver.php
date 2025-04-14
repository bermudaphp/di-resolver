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
        return ($container = $parameter->getAttributes(Container::class)[0] ?? null)
            ? $this->container->get($container->newInstance()->id) : null;
    }

    private function resolveFromType(ReflectionParameter $parameter): ?array
    {
        $type = $parameter->getType();

        if ($type instanceof \ReflectionNamedType) {
            if ($this->container->has($type->getName())) {
                return [$parameter->getName(), $this->container->get($type->getName())];
            }
            return null;
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $namedType) {
                if ($namedType instanceof \ReflectionNamedType && $this->container->has($namedType->getName())) {
                    return [$parameter->getName(), $this->container->get($namedType->getName())];
                }
            }
        }

        return null;
    }

    private function resolveFromConfig(ReflectionParameter $parameter): mixed
    {
        $config = $parameter->getAttributes(Config::class)[0] ?? null;
        if (!$config) return null;

        /**
         * @var Config $config
         */
        $config = $config->newInstance();
        $entry = $this->container->get(Config::$key);
        $path = $config->explodeDots ? explode('.', $config->path) : [$config->path];

        foreach ($path as $i => $key) {
            try {
                $entry = $entry[$key];
            } catch (\Throwable) {
                if (is_array($entry) || $entry instanceof \ArrayAccess) {
                    $pathString = implode(' → ', array_slice($path, 0, $i+1));
                    throw new ResolverException("Undefined config key: $pathString");
                } else {
                    $pathString = implode(' → ', array_slice($path, 0, $i));
                    throw new ResolverException("Config value at path '$pathString' is not accessible");
                }
            }

            $keysTraversed[] = $key;
        }

        return $entry;
    }

    public static function createFromContainer(ContainerInterface $container): ContainerResolver
    {
        return new self($container);
    }
}
