<?php declare(strict_types = 1);

namespace PHPStan\Type;

/**
 * @template-covariant T of Type
 */
final class TypeResult
{

	/**
	 * @param T $type
	 * @param list<string> $reasons
	 */
	public function __construct(
		public readonly Type $type,
		public readonly array $reasons,
	)
	{
	}

}
