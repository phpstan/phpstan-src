<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ImplementedInTurboExtension;

/**
 * @template-covariant T of Type
 */
#[ImplementedInTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/TypeResult.cpp')]
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
