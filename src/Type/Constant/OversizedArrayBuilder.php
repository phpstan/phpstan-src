<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function array_splice;
use function array_values;
use function count;

class OversizedArrayBuilder
{

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function build(Array_ $expr, callable $getTypeCallback): Type
	{
		$isList = true;
		$valueTypes = [];
		$keyTypes = [];
		$nextAutoIndex = 0;
		$items = $expr->items;
		for ($i = 0; $i < count($items); $i++) {
			$item = $items[$i];
			if (!$item->unpack) {
				continue;
			}

			$valueType = $getTypeCallback($item->value);
			if ($valueType instanceof ConstantArrayType) {
				array_splice($items, $i, 1);
				foreach ($valueType->getKeyTypes() as $j => $innerKeyType) {
					$innerValueType = $valueType->getValueTypes()[$j];
					if ($innerKeyType->isString()->no()) {
						$keyExpr = null;
					} else {
						$keyExpr = new TypeExpr($innerKeyType);
					}
					array_splice($items, $i++, 0, [new ArrayItem(
						new TypeExpr($innerValueType),
						$keyExpr,
					)]);
				}
			} else {
				array_splice($items, $i, 1, [new ArrayItem(
					new TypeExpr($valueType->getIterableValueType()),
					new TypeExpr($valueType->getIterableKeyType()),
				)]);
			}
		}
		foreach ($items as $item) {
			if ($item->unpack) {
				throw new ShouldNotHappenException();
			}
			if ($item->key !== null) {
				$itemKeyType = $getTypeCallback($item->key);
				if (!$itemKeyType instanceof ConstantIntegerType) {
					$isList = false;
				} elseif ($itemKeyType->getValue() !== $nextAutoIndex) {
					$isList = false;
					$nextAutoIndex = $itemKeyType->getValue() + 1;
				} else {
					$nextAutoIndex++;
				}
			} else {
				$itemKeyType = new ConstantIntegerType($nextAutoIndex);
				$nextAutoIndex++;
			}

			$generalizedKeyType = $itemKeyType->generalize(GeneralizePrecision::moreSpecific());
			$keyTypes[$generalizedKeyType->describe(VerbosityLevel::precise())] = $generalizedKeyType;

			$itemValueType = $getTypeCallback($item->value);
			$generalizedValueType = $itemValueType->generalize(GeneralizePrecision::moreSpecific());
			$valueTypes[$generalizedValueType->describe(VerbosityLevel::precise())] = $generalizedValueType;
		}

		$keyType = TypeCombinator::union(...array_values($keyTypes));
		$valueType = TypeCombinator::union(...array_values($valueTypes));

		$arrayType = new ArrayType($keyType, $valueType);
		if ($isList) {
			$arrayType = AccessoryArrayListType::intersectWith($arrayType);
		}

		return TypeCombinator::intersect($arrayType, new NonEmptyArrayType(), new OversizedArrayType());
	}

}
