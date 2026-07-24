<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use function array_values;

/**
 * Registered in the DI container by AutowiredExtensionsExtension,
 * once for each interface marked with #[ExtensionInterface].
 *
 * @template T of object
 * @implements ExtensionsCollection<T>
 */
final class LazyExtensionsCollection implements ExtensionsCollection
{

	/** @var list<T>|null */
	private ?array $extensions = null;

	public function __construct(
		private Container $container,
		private string $tagName,
	)
	{
	}

	public function getAll(): array
	{
		return $this->extensions ??= array_values($this->container->getServicesByTag($this->tagName));
	}

}
