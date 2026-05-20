<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\DependencyInjection\BleedingEdgeToggle;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
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

/**
 * @api
 */
final class ConstantArrayTypeBuilder
{

	public const ARRAY_COUNT_LIMIT = 256;
	private const CLOSURES_COUNT_LIMIT = 32;

	private bool $degradeToGeneralArray = false;

	private bool $disableArrayDegradation = false;

	private ?bool $degradeClosures = null;

	private bool $oversized = false;

	private TrinaryLogic $isNonEmpty;

	/**
	 * @param list<Type> $keyTypes
	 * @param array<int, Type> $valueTypes
	 * @param list<int> $nextAutoIndexes
	 * @param array<int> $optionalKeys
	 * @param array{Type, Type}|null $unsealed
	 */
	private function __construct(
		private array $keyTypes,
		private array $valueTypes,
		private array $nextAutoIndexes,
		private array $optionalKeys,
		private TrinaryLogic $isList,
		private ?array $unsealed,
	)
	{
		$this->isNonEmpty = TrinaryLogic::createNo();
	}

	public static function createEmpty(): self
	{
		$unsealed = null;
		if (BleedingEdgeToggle::isBleedingEdge()) {
			$never = new NeverType(true);
			$unsealed = [$never, $never];
		}
		return new self([], [], [0], [], TrinaryLogic::createYes(), $unsealed);
	}

	public static function createFromConstantArray(ConstantArrayType $startArrayType): self
	{
		$builder = new self(
			$startArrayType->getKeyTypes(),
			$startArrayType->getValueTypes(),
			$startArrayType->getNextAutoIndexes(),
			$startArrayType->getOptionalKeys(),
			$startArrayType->isList(),
			$startArrayType->getUnsealedTypes(),
		);
		$builder->isNonEmpty = $startArrayType->isIterableAtLeastOnce();

		if (count($startArrayType->getKeyTypes()) > self::ARRAY_COUNT_LIMIT) {
			$builder->degradeToGeneralArray(true);
		}

		return $builder;
	}

	public function makeUnsealed(Type $keyType, Type $valueType): void
	{
		$this->unsealed = [$keyType, $valueType];
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $optional = false): void
	{
		if ($offsetType !== null) {
			$offsetType = $offsetType->toArrayKey();
		}

		if ($offsetType === null && count($this->nextAutoIndexes) === 0) {
			return;
		}

		if (!$optional) {
			$this->isNonEmpty = TrinaryLogic::createYes();
		}

		if (!$this->degradeToGeneralArray) {
			if (
				$valueType instanceof ClosureType
				&& $this->degradeClosures !== false
				&& !$this->disableArrayDegradation
			) {
				$numClosures = 1;
				foreach ($this->valueTypes as $innerType) {
					if (!($innerType instanceof ClosureType)) {
						continue;
					}

					$numClosures++;
				}

				if ($numClosures >= self::CLOSURES_COUNT_LIMIT) {
					$this->degradeClosures = true;
					$this->degradeToGeneralArray = true;
					$this->oversized = true;
				}
			}

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
					if (!is_float($newAutoIndex)) {
						$newAutoIndexes[] = $newAutoIndex;
					}

					$hasOptional = true;
				}

				$max = max($this->nextAutoIndexes);

				$this->keyTypes[] = new ConstantIntegerType($max);
				$this->valueTypes[] = $valueType;

				/** @var int|float $newAutoIndex */
				$newAutoIndex = $max + 1;
				if (!is_float($newAutoIndex)) {
					$newAutoIndexes[] = $newAutoIndex;
				}

				$this->nextAutoIndexes = array_values(array_unique($newAutoIndexes));

				if ($optional || $hasOptional) {
					$this->optionalKeys[] = count($this->keyTypes) - 1;
				}

				if (
					!$this->disableArrayDegradation
					&& count($this->keyTypes) > self::ARRAY_COUNT_LIMIT
				) {
					$this->degradeToGeneralArray = true;
					$this->oversized = true;
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
							$this->nextAutoIndexes = array_values(array_filter($this->nextAutoIndexes, static fn (int $index) => $index > $keyType->getValue()));
						}
					}
					return;
				}

				$this->keyTypes[] = $offsetType;
				$this->valueTypes[] = $valueType;

				if ($offsetType instanceof ConstantIntegerType) {
					if (count($this->nextAutoIndexes) > 0) {
						$min = min($this->nextAutoIndexes);
						$max = max($this->nextAutoIndexes);
						$offsetValue = $offsetType->getValue();
						if ($offsetValue >= 0) {
							if ($offsetValue > $min) {
								if ($offsetValue <= $max) {
									$this->isList = $this->isList->and(TrinaryLogic::createMaybe());
								} else {
									$this->isList = TrinaryLogic::createNo();
								}
							}
						} else {
							$this->isList = TrinaryLogic::createNo();
						}

						if ($offsetValue >= $max) {
							/** @var int|float $newAutoIndex */
							$newAutoIndex = $offsetValue + 1;
							if (is_float($newAutoIndex)) {
								if (!$optional) {
									$this->nextAutoIndexes = [];
								}
							} elseif (!$optional) {
								$this->nextAutoIndexes = [$newAutoIndex];
							} else {
								$this->nextAutoIndexes[] = $newAutoIndex;
							}
						}
					} else {
						$this->isList = TrinaryLogic::createNo();
					}
				} else {
					$this->isList = TrinaryLogic::createNo();
				}

				if ($optional) {
					$this->optionalKeys[] = count($this->keyTypes) - 1;
				}

				if (
					!$this->disableArrayDegradation
					&& count($this->keyTypes) > self::ARRAY_COUNT_LIMIT
				) {
					$this->degradeToGeneralArray = true;
					$this->oversized = true;
				}

				return;
			}

			$scalarTypes = $offsetType->toArrayKey()->getConstantScalarTypes();
			if (count($scalarTypes) === 0) {
				$integerRanges = TypeUtils::getIntegerRanges($offsetType);
				if (count($integerRanges) > 0) {
					foreach ($integerRanges as $integerRange) {
						$finiteTypes = $integerRange->getFiniteTypes();
						if (count($finiteTypes) === 0) {
							break;
						}

						foreach ($finiteTypes as $finiteType) {
							$scalarTypes[] = $finiteType;
						}
					}
				}
			}
			if (count($scalarTypes) > 0 && count($scalarTypes) < self::ARRAY_COUNT_LIMIT) {
				$valueTypes = $this->valueTypes;
				$unmatchedScalars = [];
				foreach ($scalarTypes as $scalarType) {
					$offsetMatch = false;

					/** @var ConstantIntegerType|ConstantStringType $keyType */
					foreach ($this->keyTypes as $i => $keyType) {
						if ($keyType->getValue() !== $scalarType->getValue()) {
							continue;
						}

						if (!$optional && in_array($i, $this->optionalKeys, true)) {
							$valueTypes[$i] = $valueType;
						} else {
							$valueTypes[$i] = TypeCombinator::union($valueTypes[$i], $valueType);
						}
						$offsetMatch = true;
					}

					if ($offsetMatch) {
						continue;
					}

					$unmatchedScalars[] = $scalarType;
				}

				$this->valueTypes = $valueTypes;

				if (count($unmatchedScalars) === 0) {
					return;
				}

				foreach ($unmatchedScalars as $scalarType) {
					$this->keyTypes[] = $scalarType;
					$this->valueTypes[] = $valueType;
					$this->optionalKeys[] = count($this->keyTypes) - 1;

					if (!($scalarType instanceof ConstantIntegerType)) {
						continue;
					}

					if (count($this->nextAutoIndexes) === 0) {
						continue;
					}

					$max = max($this->nextAutoIndexes);
					$offsetValue = $scalarType->getValue();
					if ($offsetValue < $max) {
						continue;
					}

					/** @var int|float $newAutoIndex */
					$newAutoIndex = $offsetValue + 1;
					if (is_float($newAutoIndex)) {
						continue;
					}
					$this->nextAutoIndexes[] = $newAutoIndex;
				}

				$this->isList = TrinaryLogic::createNo();

				if (
					!$this->disableArrayDegradation
					&& count($this->keyTypes) > self::ARRAY_COUNT_LIMIT
				) {
					$this->degradeToGeneralArray = true;
					$this->oversized = true;
				}

				return;
			}

			$this->isList = TrinaryLogic::createNo();

			// If the builder is already unsealed (e.g. fresh bleeding-edge
			// builder, or a PHPDoc shape like `array{a: int, ...<int, T>}`),
			// fold the unknown offset/value into the existing unsealed
			// extras instead of dropping per-key precision by degrading to a
			// general array. The actual decision between unsealed
			// ConstantArrayType and general ArrayType is then made in
			// getArray() based on whether any constant keys ended up
			// alongside these extras.
			if ($this->unsealed !== null) {
				// Existing keys whose value the new offset could overwrite
				// must widen to a union of (existing, new) — the assignment
				// might or might not have hit them.
				$residualOffset = $offsetType;
				foreach ($this->keyTypes as $i => $keyType) {
					if ($offsetType->isSuperTypeOf($keyType)->no()) {
						continue;
					}
					$this->valueTypes[$i] = TypeCombinator::union($this->valueTypes[$i], $valueType);
					$residualOffset = TypeCombinator::remove($residualOffset, $keyType);
				}

				if ($residualOffset instanceof NeverType) {
					return;
				}

				[$existingKey, $existingValue] = $this->unsealed;
				$isExplicitNever = $existingKey instanceof NeverType && $existingKey->isExplicit();
				if ($isExplicitNever) {
					$this->unsealed = [$residualOffset, $valueType];
				} else {
					$this->unsealed = [
						TypeCombinator::union($existingKey, $residualOffset),
						TypeCombinator::union($existingValue, $valueType),
					];
				}
				return;
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
		if ($this->disableArrayDegradation) {
			throw new ShouldNotHappenException();
		}

		$this->degradeToGeneralArray = true;
		$this->oversized = $this->oversized || $oversized;
	}

	public function disableClosureDegradation(): void
	{
		$this->degradeClosures = false;
	}

	public function disableArrayDegradation(): void
	{
		$this->degradeToGeneralArray = false;
		$this->oversized = false;
		$this->disableArrayDegradation = true;
	}

	public function getArray(): Type
	{
		$keyTypesCount = count($this->keyTypes);
		if ($keyTypesCount === 0) {
			if ($this->unsealed !== null) {
				[$unsealedKey, $unsealedValue] = $this->unsealed;
				$isExplicitNever = $unsealedKey instanceof NeverType && $unsealedKey->isExplicit();
				if (!$isExplicitNever) {
					$arrayType = new ArrayType($unsealedKey, $unsealedValue);
					if ($this->isNonEmpty->yes()) {
						return TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
					}
					return $arrayType;
				}
			}
			return new ConstantArrayType([], [], unsealed: $this->unsealed);
		}

		if (!$this->degradeToGeneralArray) {
			/** @var list<ConstantIntegerType|ConstantStringType> $keyTypes */
			$keyTypes = $this->keyTypes;
			$array = new ConstantArrayType($keyTypes, $this->valueTypes, $this->nextAutoIndexes, $this->optionalKeys, $this->isList, $this->unsealed);
			if ($this->isNonEmpty->yes() && !$array->isIterableAtLeastOnce()->yes()) {
				return TypeCombinator::intersect($array, new NonEmptyArrayType());
			}
			return $array;
		}

		if ($this->degradeClosures === true) {
			$itemTypes = [];
			$itemTypes[] = new CallableType();
			foreach ($this->valueTypes as $valueType) {
				if ($valueType instanceof ClosureType) {
					continue;
				}
				$itemTypes[] = $valueType;
			}
		} else {
			$itemTypes = $this->valueTypes;
		}

		$keyTypesForArray = $this->keyTypes;
		// Real unsealed extras describe additional key/value pairs that
		// belong in the degraded `ArrayType`'s key/value unions too —
		// otherwise the degraded type silently drops them.
		if ($this->unsealed !== null) {
			[$unsealedKey, $unsealedValue] = $this->unsealed;
			$isExplicitNever = $unsealedKey instanceof NeverType && $unsealedKey->isExplicit();
			if (!$isExplicitNever) {
				$keyTypesForArray[] = $unsealedKey;
				$itemTypes[] = $unsealedValue;
			}
		}

		$array = new ArrayType(
			TypeCombinator::union(...$keyTypesForArray),
			TypeCombinator::union(...$itemTypes),
		);

		$types = [];
		if ($this->isNonEmpty->yes() || count($this->optionalKeys) < $keyTypesCount) {
			$types[] = new NonEmptyArrayType();
		}

		if ($this->oversized) {
			$types[] = new OversizedArrayType();
		}

		if ($this->isList->yes()) {
			$types[] = new AccessoryArrayListType();
		}

		if (count($types) === 0) {
			return $array;
		}

		return new IntersectionType([$array, ...$types]);
	}

	public function isList(): bool
	{
		return $this->isList->yes();
	}

}
