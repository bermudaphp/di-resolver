<?php

namespace Bermuda\DI\Attribute;

use Bermuda\ParameterResolver\ParameterResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * The Config attribute is used to inject configuration values into parameters or properties.
 *
 * This attribute allows you to specify a configuration key (or path) that should be used
 * to retrieve a value from a configuration container.
 *
 * Properties:
 * - path: The configuration path used to locate the desired value. This may be a dot-separated
 *   string (e.g., "database.connections.mysql") if the configuration array is nested.
 * - default: An optional default value that will be used if the configuration key is not found.
 * - explodeDots: A flag that indicates whether to split the 'path' string by dots into an array of keys.
 *
 * The static property $key holds the identifier ("config") used to locate the overall configuration
 * resource within the container.
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
     * @param string $path The configuration path to retrieve the value from.
     *                      If explodeDots is true, the path will be split into parts using '.' as a delimiter.
     * @param mixed $default The default value to use if the configuration value is not found.
     * @param bool $explodeDots Determines whether dots in the path should act as delimiters to split the configuration key.
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
     * Retrieves a nested configuration entry based on the attribute's path.
     *
     * The attribute's path (e.g. "database.connections.mysql") is split into an array of keys if explodeDots is true.
     * This method then traverses the provided configuration array or ArrayAccess instance to retrieve the desired value.
     *
     * If a key is missing or a value is not accessible as an array, a descriptive error is thrown. The error messages
     * include the keys traversed so far, making it clear exactly where the lookup failed.
     *
     * @internal
     * @param array|\ArrayAccess $config The configuration source, typically an array or object implementing ArrayAccess.
     * @return mixed The resolved configuration entry.
     *
     * @throws \OutOfBoundsException if any key in the path is missing or if a non-array value is accessed before reaching the end.
     */
    public function getEntryFromConfig(array|\ArrayAccess $config): mixed
    {
        $path = $this->explodeDots ? explode('.', $config->path) : [$config->path];

        foreach ($path as $i => $key) {
            try {
                $entry = $entry[$key];
            } catch (\Throwable) {
                if (is_array($entry) || $entry instanceof \ArrayAccess) {
                    $pathString = implode(' → ', array_slice($path, 0, $i+1));
                    throw new \OutOfBoundsException("Undefined config key: $pathString");
                } else {
                    $pathString = implode(' → ', array_slice($path, 0, $i));
                    throw new \OutOfBoundsException("Config value at path '$pathString' is not accessible");
                }
            }
        }

        return $entry;
    }
}
