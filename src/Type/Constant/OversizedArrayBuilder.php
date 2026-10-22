<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function array_values;
use const PHP_INT_MAX;

#[AutowiredService]
final class OversizedArrayBuilder
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

		// unpacked items are expanded into [key type or null for an implicit key, value type]
		// pairs right here - the callback only knows the literal's own sub-expressions
		$entries = [];
		foreach ($expr->items as $item) {
			if ($item->unpack) {
				$unpackedType = $getTypeCallback($item->value);
				if ($unpackedType instanceof ConstantArrayType) {
					foreach ($unpackedType->getKeyTypes() as $j => $innerKeyType) {
						$entries[] = [
							$innerKeyType->isString()->no() ? null : $innerKeyType,
							$unpackedType->getValueTypes()[$j],
						];
					}
				} else {
					$entries[] = [$unpackedType->getIterableKeyType(), $unpackedType->getIterableValueType()];
				}
				continue;
			}

			$entries[] = [
				$item->key !== null ? $getTypeCallback($item->key) : null,
				$getTypeCallback($item->value),
			];
		}

		foreach ($entries as [$itemKeyType, $itemValueType]) {
			if ($itemKeyType !== null) {
				if (!$itemKeyType instanceof ConstantIntegerType) {
					$isList = false;
				} elseif ($itemKeyType->getValue() !== $nextAutoIndex) {
					$isList = false;
					$nextAutoIndex = $this->advanceAutoIndex($itemKeyType->getValue());
				} else {
					$nextAutoIndex = $this->advanceAutoIndex($nextAutoIndex);
				}
			} elseif ($nextAutoIndex === null) {
				$isList = false;
				$itemKeyType = new IntegerType();
			} else {
				$itemKeyType = new ConstantIntegerType($nextAutoIndex);
				$nextAutoIndex = $this->advanceAutoIndex($nextAutoIndex);
			}

			$generalizedKeyType = $itemKeyType->generalize(GeneralizePrecision::moreSpecific());
			$keyTypes[$generalizedKeyType->describe(VerbosityLevel::precise())] = $generalizedKeyType;

			$generalizedValueType = $itemValueType->generalize(GeneralizePrecision::moreSpecific());
			$valueTypes[$generalizedValueType->describe(VerbosityLevel::precise())] = $generalizedValueType;
		}

		$keyType = TypeCombinator::union(...array_values($keyTypes));
		$valueType = TypeCombinator::union(...array_values($valueTypes));

		$arrayType = new ArrayType($keyType, $valueType);
		$accessories = [];
		if ($isList) {
			$accessories[] = new AccessoryArrayListType();
		}

		return TypeCombinator::intersect($arrayType, new NonEmptyArrayType(), new OversizedArrayType(), ...$accessories);
	}

	/**
	 * Null means the next implicit key is unpredictable: PHP throws
	 * "Cannot add element to the array as the next element is already occupied"
	 * instead of wrapping the auto-index around to a float.
	 */
	private function advanceAutoIndex(int $index): ?int
	{
		return $index === PHP_INT_MAX ? null : $index + 1;
	}

}
