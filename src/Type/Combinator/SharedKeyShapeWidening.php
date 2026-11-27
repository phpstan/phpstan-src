<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use function array_key_exists;
use function count;

/**
 * The non-empty shapes with different keys fold into one shape, the keys
 * missing from some of them becoming optional - as loop generalization folds
 * them. Which keys go together is lost, but unlike degrading them, every key
 * keeps its own value type.
 *
 * Only shapes that all share a required key fold: they are variants of one
 * record. Shapes without one, such as lookup maps of disjoint keys, would fold
 * into a shape of all their keys, too big to keep inside the shape enclosing
 * them. And the fold is kept only when it is small enough; otherwise the shapes
 * are left for the next step.
 *
 * @internal
 */
final class SharedKeyShapeWidening implements WideningStep
{

	public function apply(array $types): array
	{
		$merged = null;
		$result = [];
		foreach ($types as $type) {
			$shape = ArrayWidening::asShape($type);
			if ($shape === null || !$shape->isIterableAtLeastOnce()->yes()) {
				$result[] = $type;
				continue;
			}
			if ($merged === null) {
				$merged = $shape;
				continue;
			}
			$merged = self::fold($merged, $shape);
			if ($merged === null) {
				return $types;
			}
		}
		if ($merged === null || count($result) + 1 >= count($types)) {
			return $types;
		}

		$merged = ArrayWidening::widenValueTypes($merged);
		if (count($merged->getOptionalKeys()) >= count($merged->getKeyTypes())) {
			return $types;
		}

		$result[] = $merged;
		return ArrayWidening::isWithinLimit($result) ? $result : $types;
	}

	private static function fold(ConstantArrayType $merged, ConstantArrayType $shape): ?ConstantArrayType
	{
		if ($merged->getUnsealedTypes() === null || $shape->getUnsealedTypes() === null) {
			// the legacy merge keeps only the keys of $merged - add the
			// keys only $shape has to it first, as optional keys
			$mergedKeys = [];
			foreach ($merged->getKeyTypes() as $keyType) {
				$mergedKeys[$keyType->getValue()] = true;
			}
			$builder = null;
			foreach ($shape->getKeyTypes() as $i => $keyType) {
				if (array_key_exists($keyType->getValue(), $mergedKeys)) {
					continue;
				}
				$builder ??= ConstantArrayTypeBuilder::createFromConstantArray($merged);
				$builder->setOffsetValueType($keyType, $shape->getValueTypes()[$i], true);
			}
			if ($builder !== null) {
				$extended = ArrayWidening::asShape($builder->getArray());
				if ($extended === null) {
					return null;
				}
				$merged = $extended;
			}
		}

		return $merged->mergeWith($shape);
	}

}
