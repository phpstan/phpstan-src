<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\ShouldNotHappenException;

/**
 * @internal
 * @template-covariant T of object
 * @implements ExtensionsCollection<T>
 */
final class LazyExtensionsCollection implements ExtensionsCollection
{

	/** @var list<T>|null */
	private ?array $extensions = null;

	/**
	 * @param class-string<T> $interfaceName
	 */
	public function __construct(private ?Container $container, private string $interfaceName)
	{
	}

	public function getAll(): array
	{
		if ($this->extensions === null) {
			if ($this->container === null) {
				throw new ShouldNotHappenException();
			}

			$this->extensions = $this->container->getExtensions($this->interfaceName);

			// Collections are held by long-lived objects like ClassPropertiesNode. Keeping the
			// container reference here would make each of them a transitive handle on the entire
			// DI container. After the extensions are resolved the container is no longer needed.
			$this->container = null;
		}

		return $this->extensions;
	}

}
