<?php

namespace Bermuda\DI\Attribute;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Attribute for dependency injection resolution.
 * 
 * Used to mark parameters or properties that should be resolved from a DI container.
 * When applied to a parameter, the resolver will attempt to fetch the service 
 * from the container using the specified ID or the parameter's type if no ID is provided.
 * 
 * @example Basic usage with explicit service ID:
 * ```php
 * function processUser(#[Inject('user.repository')] UserRepository $repo) {
 *     // $repo will be resolved from container using 'user.repository' ID
 * }
 * ```
 * 
 * @example Usage without ID (resolves by parameter type):
 * ```php
 * function processUser(#[Inject] UserRepository $repo) {
 *     // $repo will be resolved from container using 'UserRepository' as ID
 * }
 * ```
 * 
 * @example Property injection:
 * ```php
 * class UserService {
 *     #[Inject('logger.service')]
 *     private LoggerInterface $logger;
 * }
 * ```
 */
#[\Attribute(\Attribute::TARGET_PARAMETER|\Attribute::TARGET_PROPERTY)]
class Inject
{
    /**
     * Creates a new Inject attribute instance.
     * 
     * @param string|null $id The service identifier in the DI container.
     *                        If null, the parameter's type will be used as the service ID.
     *                        For example, if $id is null and parameter type is UserRepository,
     *                        the resolver will try to get 'UserRepository' from the container.
     */
    public function __construct(
        public readonly ?string $id = null,
    ) {
    }
}
