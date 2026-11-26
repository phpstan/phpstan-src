<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Type;

/**
 * One step of ArrayWidening: it gives up some precision of the array shapes
 * among the types to make them hold fewer constant array values.
 *
 * @internal
 */
interface WideningStep
{

	/**
	 * @param list<Type> $types
	 * @return list<Type>
	 */
	public function apply(array $types): array;

}
