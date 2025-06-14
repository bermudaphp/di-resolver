<?php

namespace Bermuda\DI\Attribute;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Bermuda\ParameterResolver\ParameterResolutionException;

/**
 * The Config attribute is used to inject configuration values into parameters or properties.
 *
 * This attribute allows you to specify a configuration key (or path) to be used
 * for retrieving a value from a configuration container.
 *
 * Properties:
 * - path: The configuration path used to locate the desired value. The path can be specified
 *   as a dot-separated string (e.g., "database.connections.mysql") if the configuration is nested.
 * - default: An optional default value that will be used if the configuration key is not found.
 * - explodeDots: A flag indicating whether the 'path' string should be split into parts using dots.
 *
 * The static property $key holds the identifier ("config") used to retrieve the root configuration
 * resource from the container.
 *
 * Example usage:
 * public function __construct(#[Config("app.settings.debug", default: false, explodeDots: true)] $debugConfig) { ... }
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class Config
{
    /**
     * Constructor.
     *
     * @param string $path The configuration path from which to retrieve the value.
     *                     If explodeDots is true, the path will be split into parts using '.' as the delimiter.
     * @param mixed $default The default value to use if the configuration value is not found.
     * @param bool $explodeDots Determines whether the dot character should be used to split the configuration key.
     */
    public function __construct(
        public readonly string $path,
        public readonly mixed $default = null,
        public readonly bool $explodeDots = true,
    ) {}

    /**
     * Static key used to retrieve the root configuration from the container.
     *
     * This key is used by the dependency injection mechanism to locate the configuration data.
     *
     * @var string
     */
    public static string $key = 'config';

    /**
     * Retrieves the nested configuration entry based on the attribute's path.
     *
     * The attribute's path (e.g., "database.connections.mysql") is split into an array of keys
     * if explodeDots is true. The method then traverses the provided configuration array or ArrayAccess
     * object to extract the desired value.
     *
     * If any key in the path is missing, or if a value is not accessible as an array, a descriptive exception is thrown.
     * The error messages include the keys traversed so far, clearly indicating the point of failure.
     *
     * @internal
     * @param array|\ArrayAccess $config The configuration source, typically an array or an object implementing ArrayAccess.
     * @return mixed The extracted configuration value.
     *
     * @throws \OutOfBoundsException if any key in the path is missing.
     * @throws \InvalidArgumentException if the configuration value at a given path is not accessible.
     */
    public function getEntryFromConfig(array|\ArrayAccess $config): mixed
    {
        $path = $this->explodeDots ? explode('.', $this->path) : [$this->path];
        $entry = $config;

        foreach ($path as $i => $key) {
            if (!is_array($entry) && !$entry instanceof \ArrayAccess) {
                $pathString = implode(' → ', array_slice($path, 0, $i));
                throw new \InvalidArgumentException("The configuration value at path '$pathString' is not accessible");
            }

            if (!isset($entry[$key]) && !array_key_exists($key, $entry)) {
                $pathString = implode(' → ', array_slice($path, 0, $i + 1));
                throw new \OutOfBoundsException("Undefined configuration key: $pathString");
            }

            $entry = $entry[$key];
        }

        return $entry;
    }
}
