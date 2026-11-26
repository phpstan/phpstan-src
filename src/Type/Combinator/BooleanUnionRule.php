<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\BooleanType;
use PHPStan\Type\TypeKind;
use function min;

/**
 * `true|false` is `bool`, which takes the place of the first of them.
 *
 * @internal
 */
final class BooleanUnionRule implements UnionRule
{

	public function getKindMask(): int
	{
		return TypeKind::BOOL;
	}

	public function apply(array $types, array $normalizedGroups): array
	{
		$trueIndex = null;
		$falseIndex = null;
		foreach ($types as $i => $type) {
			if (($type->getKindMask() & ~TypeKind::BOOL) !== 0 || !$type->isBoolean()->yes() || !$type->isConstantScalarValue()->yes() || $type->hasTemplateOrLateResolvableType()) {
				continue;
			}
			if ($type->isTrue()->yes()) {
				$trueIndex = $i;
			} elseif ($type->isFalse()->yes()) {
				$falseIndex = $i;
			}
		}
		if ($trueIndex === null || $falseIndex === null) {
			return $types;
		}

		$firstIndex = min($trueIndex, $falseIndex);
		$result = [];
		foreach ($types as $i => $type) {
			if ($i === $firstIndex) {
				$result[] = new BooleanType();
				continue;
			}
			if ($i === $trueIndex || $i === $falseIndex) {
				continue;
			}
			$result[] = $type;
		}

		return $result;
	}

}
