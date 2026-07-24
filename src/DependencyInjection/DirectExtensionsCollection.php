<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use function array_values;

/**
 * Useful in tests as a substitute for the lazy container-backed collection.
 *
 * @template T of object
 * @implements ExtensionsCollection<T>
 */
final class DirectExtensionsCollection implements ExtensionsCollection
{

	/** @var list<T> */
	private array $extensions;

	/**
	 * @param array<T> $extensions
	 */
	public function __construct(array $extensions)
	{
		$this->extensions = array_values($extensions);
	}

	public function getAll(): array
	{
		return $this->extensions;
	}

}
