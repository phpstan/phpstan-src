<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function get_class;

/**
 * A shape without another shape of the same keys that differs from it in
 * the value at one required key only is the shape with that value
 * removed: `array{name: BackedEnum|string}` without `array{name: string}`
 * is `array{name: BackedEnum}`. union() merges such shapes into one, and
 * this takes them apart again.
 *
 * @internal
 */
final class ShapeRemoveRule implements RemoveRule
{

	public function tryRemove(Type $fromType, Type $typeToRemove): ?Type
	{
		if (
			get_class($fromType) !== ConstantArrayType::class
			|| get_class($typeToRemove) !== ConstantArrayType::class
			|| $fromType->isUnsealed()->yes()
			|| $typeToRemove->isUnsealed()->yes()
		) {
			return null;
		}

		$keyTypes = $fromType->getKeyTypes();
		$otherKeyTypes = $typeToRemove->getKeyTypes();
		if (count($keyTypes) !== count($otherKeyTypes) || $fromType->getOptionalKeys() !== $typeToRemove->getOptionalKeys()) {
			return null;
		}

		$valueTypes = $fromType->getValueTypes();
		$otherValueTypes = $typeToRemove->getValueTypes();
		$differingKey = null;
		foreach ($keyTypes as $i => $keyType) {
			if ($keyType->getValue() !== $otherKeyTypes[$i]->getValue()) {
				return null;
			}
			if ($valueTypes[$i]->equals($otherValueTypes[$i])) {
				continue;
			}
			if ($differingKey !== null || $fromType->isOptionalKey($i)) {
				return null;
			}
			$differingKey = $i;
		}
		if ($differingKey === null) {
			return null;
		}

		$valueType = TypeCombinator::remove($valueTypes[$differingKey], $otherValueTypes[$differingKey]);
		if ($valueType instanceof NeverType) {
			return $valueType;
		}
		$valueTypes[$differingKey] = $valueType;

		return new ConstantArrayType(
			$keyTypes,
			$valueTypes,
			$fromType->getNextAutoIndexes(),
			$fromType->getOptionalKeys(),
			$fromType->isList(),
			$fromType->getUnsealedTypes(),
		);
	}

}
