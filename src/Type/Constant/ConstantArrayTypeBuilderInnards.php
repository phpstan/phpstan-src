<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function is_float;
use function max;
use function min;

class ConstantArrayTypeBuilderInnards
{

	public bool $degradeToGeneralArray = false;

	private bool $oversized = false;

	/**
	 * @param array<int, Type> $keyTypes
	 * @param array<int, Type> $valueTypes
	 * @param non-empty-list<int> $nextAutoIndexes
	 * @param array<int> $optionalKeys
	 */
	public function __construct(
		public array $keyTypes,
		public array $valueTypes,
		public array $nextAutoIndexes,
		public array $optionalKeys,
		public TrinaryLogic $isList,
	)
	{
	}

	public function duplicate(): self
	{
		return new self(
			$this->keyTypes,
			$this->valueTypes,
			$this->nextAutoIndexes,
			$this->optionalKeys,
			$this->isList,
		);
	}

	public function setValueTypeForNewOffset(Type $valueType, bool $optional): void
	{
		$newAutoIndexes = $optional ? $this->nextAutoIndexes : [];
		$hasOptional = false;
		foreach ($this->keyTypes as $i => $keyType) {
			if (!$keyType instanceof ConstantIntegerType) {
				continue;
			}

			if (!in_array($keyType->getValue(), $this->nextAutoIndexes, true)) {
				continue;
			}

			$this->valueTypes[$i] = TypeCombinator::union($this->valueTypes[$i], $valueType);

			if (!$hasOptional && !$optional) {
				$this->optionalKeys = array_values(array_filter($this->optionalKeys, static fn (int $index): bool => $index !== $i));
			}

			/** @var int|float $newAutoIndex */
			$newAutoIndex = $keyType->getValue() + 1;
			if (is_float($newAutoIndex)) {
				$newAutoIndex = $keyType->getValue();
			}

			$newAutoIndexes[] = $newAutoIndex;
			$hasOptional = true;
		}

		$max = max($this->nextAutoIndexes);

		$this->keyTypes[] = new ConstantIntegerType($max);
		$this->valueTypes[] = $valueType;

		/** @var int|float $newAutoIndex */
		$newAutoIndex = $max + 1;
		if (is_float($newAutoIndex)) {
			$newAutoIndex = $max;
		}

		$newAutoIndexes[] = $newAutoIndex;
		$this->nextAutoIndexes = array_values(array_unique($newAutoIndexes));

		if ($optional || $hasOptional) {
			$this->optionalKeys[] = count($this->keyTypes) - 1;
		}

		if (count($this->keyTypes) <= ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
			return;
		}

		$this->degradeToGeneralArray = true;
	}

	public function setValueTypeForConstantOffset(ConstantIntegerType|ConstantStringType $offsetType, Type $valueType, bool $optional): void
	{
		/** @var ConstantIntegerType|ConstantStringType $keyType */
		foreach ($this->keyTypes as $i => $keyType) {
			if ($keyType->getValue() !== $offsetType->getValue()) {
				continue;
			}

			if ($optional) {
				$valueType = TypeCombinator::union($valueType, $this->valueTypes[$i]);
			}

			$this->valueTypes[$i] = $valueType;

			if (!$optional) {
				$this->optionalKeys = array_values(array_filter($this->optionalKeys, static fn (int $index): bool => $index !== $i));
				if ($keyType instanceof ConstantIntegerType) {
					$nextAutoIndexes = array_values(array_filter($this->nextAutoIndexes, static fn (int $index) => $index > $keyType->getValue()));
					if (count($nextAutoIndexes) === 0) {
						throw new ShouldNotHappenException();
					}
					$this->nextAutoIndexes = $nextAutoIndexes;
				}
			}
			return;
		}

		$this->keyTypes[] = $offsetType;
		$this->valueTypes[] = $valueType;

		if ($offsetType instanceof ConstantIntegerType) {
			$min = min($this->nextAutoIndexes);
			$max = max($this->nextAutoIndexes);
			if ($offsetType->getValue() > $min) {
				if ($offsetType->getValue() <= $max) {
					$this->isList = $this->isList->and(TrinaryLogic::createMaybe());
				} else {
					$this->isList = TrinaryLogic::createNo();
				}
			}
			if ($offsetType->getValue() >= $max) {
				/** @var int|float $newAutoIndex */
				$newAutoIndex = $offsetType->getValue() + 1;
				if (is_float($newAutoIndex)) {
					$newAutoIndex = $max;
				}
				if (!$optional) {
					$this->nextAutoIndexes = [$newAutoIndex];
				} else {
					$this->nextAutoIndexes[] = $newAutoIndex;
				}
			}
		} else {
			$this->isList = TrinaryLogic::createNo();
		}

		if ($optional) {
			$this->optionalKeys[] = count($this->keyTypes) - 1;
		}

		if (count($this->keyTypes) <= ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
			return;
		}

		$this->degradeToGeneralArray = true;
	}

	public function setValueTypeForGeneralOffset(?Type $offsetType, Type $valueType, bool $optional): void
	{
		$this->isList = TrinaryLogic::createNo();
		if ($offsetType === null) {
			$offsetType = TypeCombinator::union(...array_map(static fn (int $index) => new ConstantIntegerType($index), $this->nextAutoIndexes));
		}

		$this->keyTypes[] = $offsetType;
		$this->valueTypes[] = $valueType;
		if ($optional) {
			$this->optionalKeys[] = count($this->keyTypes) - 1;
		}
		$this->degradeToGeneralArray = true;
	}

	public function degradeToGeneralArray(bool $oversized): void
	{
		$this->degradeToGeneralArray = true;
		$this->oversized = $this->oversized || $oversized;
	}

	public function getArray(): Type
	{
		$keyTypesCount = count($this->keyTypes);
		if ($keyTypesCount === 0) {
			return new ConstantArrayType([], []);
		}

		if (!$this->degradeToGeneralArray) {
			/** @var array<int, ConstantIntegerType|ConstantStringType> $keyTypes */
			$keyTypes = $this->keyTypes;
			return new ConstantArrayType($keyTypes, $this->valueTypes, $this->nextAutoIndexes, $this->optionalKeys, $this->isList);
		}

		$array = new ArrayType(
			TypeCombinator::union(...$this->keyTypes),
			TypeCombinator::union(...$this->valueTypes),
		);

		if (count($this->optionalKeys) < $keyTypesCount) {
			$array = TypeCombinator::intersect($array, new NonEmptyArrayType());
		}

		if ($this->oversized) {
			$array = TypeCombinator::intersect($array, new OversizedArrayType());
		}

		if ($this->isList->yes()) {
			$array = AccessoryArrayListType::intersectWith($array);
		}

		return $array;
	}

}
