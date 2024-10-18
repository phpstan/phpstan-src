<?php declare(strict_types = 1);

namespace PHPStan\Type;

/**
 * @template-covariant T of Type
 */
final class TypeResult
{

	public readonly Type $type;

	/** @var list<string> */
	public readonly array $reasons;

	/**
	 * @param T $type
	 * @param list<string> $reasons
	 */
	public function __construct(
		Type $type,
		array $reasons,
	)
	{
		$this->type = $type;
		$this->reasons = $reasons;
	}

}
