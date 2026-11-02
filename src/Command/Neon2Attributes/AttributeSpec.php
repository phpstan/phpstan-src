<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

/**
 * An attribute from the PHPStan\DependencyInjection namespace to write into a PHP file.
 */
final class AttributeSpec
{

	/**
	 * @param non-empty-string $name short name of the attribute class
	 * @param array<non-empty-string, mixed> $arguments named arguments; the values are scalars, lists and maps of them, ClassReference and AttributeSpec for `new`
	 */
	public function __construct(
		public string $name,
		public array $arguments = [],
	)
	{
	}

}
