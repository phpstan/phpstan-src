<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

/**
 * @api
 * @template-covariant T of object
 * @implements ExtensionsCollection<T>
 */
final class DirectExtensionsCollection implements ExtensionsCollection
{

	/**
	 * @param list<T> $extensions
	 */
	public function __construct(private array $extensions)
	{
	}

	public function getAll(): array
	{
		return $this->extensions;
	}

}
