<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

/**
 * Collection of all registered extensions implementing
 * an interface marked with the #[ExtensionInterface] attribute.
 *
 * Inject it into a service constructor with the #[AutowiredExtensions] attribute:
 *
 * ```
 * public function __construct(
 *     #[AutowiredExtensions(of: MyExtension::class)]
 *     private ExtensionsCollection $extensions,
 * )
 * ```
 *
 * @api
 * @template-covariant T of object
 */
interface ExtensionsCollection
{

	/**
	 * @return list<T>
	 */
	public function getAll(): array;

}
