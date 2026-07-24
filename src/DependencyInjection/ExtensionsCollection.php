<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

/**
 * All extensions registered under a single extension interface.
 *
 * Inject it by putting #[AutowiredExtensions] above a constructor parameter typed
 * as ExtensionsCollection, with the extension interface in the PHPDoc generic type.
 *
 * The extensions are resolved from the DI container on the first getAll() call,
 * so a collection can be injected into a service that the extensions themselves depend on.
 *
 * @api
 * @api-do-not-implement
 * @template-covariant T of object
 */
interface ExtensionsCollection
{

	/**
	 * @return list<T>
	 */
	public function getAll(): array;

}
