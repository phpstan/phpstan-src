<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\IterableType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeKind;
use function count;

/**
 * Iterables merge into one iterable of all their keys and all their values, as
 * `iterable<string>|iterable<int>` becomes `iterable<int|string>`, which takes
 * the place of the first of them. This loses which keys go with which values.
 *
 * @internal
 */
final class IterableUnionRule implements UnionRule
{

	public function getKindMask(): int
	{
		return TypeKind::ARRAY | TypeKind::OBJECT;
	}

	public function mergesConstantScalarValues(): bool
	{
		return true;
	}

	public function apply(array $types, array $normalizedGroups): array
	{
		$iterables = [];
		foreach ($types as $i => $type) {
			if (!$type instanceof IterableType) {
				continue;
			}
			$iterables[$i] = $type;
		}
		if (count($iterables) < 2) {
			return $types;
		}

		$keyTypes = [];
		$valueTypes = [];
		foreach ($iterables as $iterable) {
			$keyTypes[] = $iterable->getIterableKeyType();
			$valueTypes[] = $iterable->getIterableValueType();
		}
		$merged = new IterableType(TypeCombinator::union(...$keyTypes), TypeCombinator::union(...$valueTypes));

		$result = [];
		$placed = false;
		foreach ($types as $i => $type) {
			if (!isset($iterables[$i])) {
				$result[] = $type;
				continue;
			}
			if ($placed) {
				continue;
			}
			$result[] = $merged;
			$placed = true;
		}

		return $result;
	}

}
