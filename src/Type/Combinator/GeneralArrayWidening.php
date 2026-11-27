<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\TypeCombinator;
use function count;

/**
 * The general arrays merge into one, as GeneralArrayUnionRule merges them in
 * the union that follows: merging them widens their values together, so the
 * shapes nested in them fold with each other instead of each degrading on its
 * own in OversizedArrayWidening.
 *
 * @internal
 */
final class GeneralArrayWidening implements WideningStep
{

	public function apply(array $types): array
	{
		$generalArrays = [];
		$result = [];
		foreach ($types as $type) {
			if ($type->isArray()->yes() && $type->getConstantArrays() === []) {
				$generalArrays[] = $type;
				continue;
			}
			$result[] = $type;
		}
		if (count($generalArrays) < 2) {
			return $types;
		}

		$result[] = TypeCombinator::union(...$generalArrays);
		return $result;
	}

}
