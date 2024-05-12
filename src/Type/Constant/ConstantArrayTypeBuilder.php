<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\Php\PhpVersion;
use PHPStan\Php\PhpVersionFactory;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function is_float;
use function max;
use function min;
use function range;

/** @api */
class ConstantArrayTypeBuilder
{

	public const ARRAY_COUNT_LIMIT = 256;

	private bool $degradeToGeneralArray = false;

	private bool $oversized = false;

	/**
	 * @param array<int, Type> $keyTypes
	 * @param array<int, Type> $valueTypes
	 * @param non-empty-list<int> $nextAutoIndexes
	 * @param array<int> $optionalKeys
	 */
	private function __construct(
		private readonly PhpVersion $phpVersion,
		private array $keyTypes,
		private array $valueTypes,
		private array $nextAutoIndexes,
		private array $optionalKeys,
		private TrinaryLogic $isList,
	)
	{
	}

	public static function createEmpty(): self
	{
		return new self((new PhpVersionFactory(null, null))->create(), [], [], [0], [], TrinaryLogic::createYes());
	}

	public static function createFromConstantArray(ConstantArrayType $startArrayType): self
	{
		$builder = new self(
			(new PhpVersionFactory(null, null))->create(),
			$startArrayType->getKeyTypes(),
			$startArrayType->getValueTypes(),
			$startArrayType->getNextAutoIndexes(),
			$startArrayType->getOptionalKeys(),
			$startArrayType->isList(),
		);

		if (count($startArrayType->getKeyTypes()) > self::ARRAY_COUNT_LIMIT) {
			$builder->degradeToGeneralArray(true);
		}

		return $builder;
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $optional = false): void
	{
		if ($offsetType !== null) {
			$offsetType = $offsetType->toArrayKey();
		}

		if (!$this->degradeToGeneralArray) {
			if ($offsetType === null) {
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

				if (count($this->keyTypes) > self::ARRAY_COUNT_LIMIT) {
					$this->degradeToGeneralArray = true;
				}

				return;
			}

			if ($offsetType instanceof ConstantIntegerType || $offsetType instanceof ConstantStringType) {
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
					if ($this->phpVersion->supportsAssigningANegativeIndexToAnEmptyArray() && count($this->keyTypes) === 1 && $offsetType->getValue() < 0) {
						$newAutoIndex = $offsetType->getValue() + 1;
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

				if (count($this->keyTypes) > self::ARRAY_COUNT_LIMIT) {
					$this->degradeToGeneralArray = true;
				}

				return;
			}

			$this->isList = TrinaryLogic::createNo();

			$scalarTypes = $offsetType->getConstantScalarTypes();
			if (count($scalarTypes) === 0) {
				$integerRanges = TypeUtils::getIntegerRanges($offsetType);
				if (count($integerRanges) > 0) {
					foreach ($integerRanges as $integerRange) {
						if ($integerRange->getMin() === null) {
							break;
						}
						if ($integerRange->getMax() === null) {
							break;
						}

						$rangeLength = $integerRange->getMax() - $integerRange->getMin();
						if ($rangeLength >= self::ARRAY_COUNT_LIMIT) {
							$scalarTypes = [];
							break;
						}

						foreach (range($integerRange->getMin(), $integerRange->getMax()) as $rangeValue) {
							$scalarTypes[] = new ConstantIntegerType($rangeValue);
						}
					}
				}
			}
			if (count($scalarTypes) > 0 && count($scalarTypes) < self::ARRAY_COUNT_LIMIT) {
				$match = true;
				$valueTypes = $this->valueTypes;
				foreach ($scalarTypes as $scalarType) {
					$scalarOffsetType = $scalarType->toArrayKey();
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

		if ($offsetType === null) {
			$offsetType = TypeCombinator::union(...array_map(static fn (int $index) => new ConstantIntegerType($index), $this->nextAutoIndexes));
		} else {
			$this->isList = TrinaryLogic::createNo();
		}

		$this->keyTypes[] = $offsetType;
		$this->valueTypes[] = $valueType;
		if ($optional) {
			$this->optionalKeys[] = count($this->keyTypes) - 1;
		}
		$this->degradeToGeneralArray = true;
	}

	public function degradeToGeneralArray(bool $oversized = false): void
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

	public function isList(): bool
	{
		return $this->isList->yes();
	}

}
