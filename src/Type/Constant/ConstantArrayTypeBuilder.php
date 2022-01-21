<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_filter;
use function array_values;
use function count;
use function is_float;
use function max;
use function range;

/** @api */
class ConstantArrayTypeBuilder
{

	public const ARRAY_COUNT_LIMIT = 256;

	private bool $degradeToGeneralArray = false;

	/**
	 * @param array<int, Type> $keyTypes
	 * @param array<int, Type> $valueTypes
	 * @param array<int> $optionalKeys
	 */
	private function __construct(
		private array $keyTypes,
		private array $valueTypes,
		private int $nextAutoIndex,
		private array $optionalKeys,
	)
	{
	}

	public static function createEmpty(): self
	{
		return new self([], [], 0, []);
	}

	public static function createFromConstantArray(ConstantArrayType $startArrayType): self
	{
		$builder = new self(
			$startArrayType->getKeyTypes(),
			$startArrayType->getValueTypes(),
			$startArrayType->getNextAutoIndex(),
			$startArrayType->getOptionalKeys(),
		);

		if (count($startArrayType->getKeyTypes()) > self::ARRAY_COUNT_LIMIT) {
			$builder->degradeToGeneralArray();
		}

		return $builder;
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $optional = false): void
	{
		if ($offsetType === null) {
			$offsetType = new ConstantIntegerType($this->nextAutoIndex);
		} else {
			$offsetType = ArrayType::castToArrayKeyType($offsetType);
		}

		if (!$this->degradeToGeneralArray) {
			if ($offsetType instanceof ConstantIntegerType || $offsetType instanceof ConstantStringType) {
				/** @var ConstantIntegerType|ConstantStringType $keyType */
				foreach ($this->keyTypes as $i => $keyType) {
					if ($keyType->getValue() === $offsetType->getValue()) {
						$this->valueTypes[$i] = $valueType;
						$this->optionalKeys = array_values(array_filter($this->optionalKeys, static fn (int $index): bool => $index !== $i));
						return;
					}
				}

				$this->keyTypes[] = $offsetType;
				$this->valueTypes[] = $valueType;

				if ($optional) {
					$this->optionalKeys[] = count($this->keyTypes) - 1;
				}

				/** @var int|float $newNextAutoIndex */
				$newNextAutoIndex = $offsetType instanceof ConstantIntegerType
					? max($this->nextAutoIndex, $offsetType->getValue() + 1)
					: $this->nextAutoIndex;
				if (!is_float($newNextAutoIndex)) {
					$this->nextAutoIndex = $newNextAutoIndex;
				}

				if (count($this->keyTypes) > self::ARRAY_COUNT_LIMIT) {
					$this->degradeToGeneralArray = true;
				}

				return;
			}

			$scalarTypes = TypeUtils::getConstantScalars($offsetType);
			if (count($scalarTypes) === 0) {
				$integerRanges = TypeUtils::getIntegerRanges($offsetType);
				if (count($integerRanges) > 0) {
					foreach ($integerRanges as $integerRange) {
						$minRange = $integerRange->getMin();
						if ($minRange === null || $minRange <= -self::ARRAY_COUNT_LIMIT) {
							break;
						}

						$maxRange = $integerRange->getMax();
                        if ($maxRange === null || $maxRange >= self::ARRAY_COUNT_LIMIT) {
							break;
						}

						foreach (range($minRange, $maxRange) as $rangeValue) {
							$scalarTypes[] = new ConstantIntegerType($rangeValue);
						}
					}
				}
			}
			if (count($scalarTypes) > 0 && count($scalarTypes) < self::ARRAY_COUNT_LIMIT) {
				$match = true;
				$valueTypes = $this->valueTypes;
				foreach ($scalarTypes as $scalarType) {
					$scalarOffsetType = ArrayType::castToArrayKeyType($scalarType);
					if (!$scalarOffsetType instanceof ConstantIntegerType && !$scalarOffsetType instanceof ConstantStringType) {
						throw new ShouldNotHappenException();
					}
					$offsetMatch = false;

					/** @var ConstantIntegerType|ConstantStringType $keyType */
					foreach ($this->keyTypes as $i => $keyType) {
						if ($keyType->getValue() !== $scalarOffsetType->getValue()) {
							continue;
						}

						$valueTypes[$i] = TypeCombinator::union($valueTypes[$i], $valueType);
						$offsetMatch = true;
					}

					if ($offsetMatch) {
						continue;
					}

					$match = false;
				}

				if ($match) {
					$this->valueTypes = $valueTypes;
					return;
				}
			}
		}

		$this->keyTypes[] = $offsetType;
		$this->valueTypes[] = $valueType;
		if ($optional) {
			$this->optionalKeys[] = count($this->keyTypes) - 1;
		}
		$this->degradeToGeneralArray = true;
	}

	public function degradeToGeneralArray(): void
	{
		$this->degradeToGeneralArray = true;
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
			return new ConstantArrayType($keyTypes, $this->valueTypes, $this->nextAutoIndex, $this->optionalKeys);
		}

		$array = new ArrayType(
			TypeCombinator::union(...$this->keyTypes),
			TypeCombinator::union(...$this->valueTypes),
		);

		if (count($this->optionalKeys) < $keyTypesCount) {
			return TypeCombinator::intersect($array, new NonEmptyArrayType());
		}

		return $array;
	}

}
