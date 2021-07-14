<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_filter;

/** @api */
class ConstantArrayTypeBuilder
{

	/** @var array<int, Type> */
	private array $keyTypes;

	/** @var array<int, Type> */
	private array $valueTypes;

	/** @var array<int> */
	private array $optionalKeys;

	private int $nextAutoIndex;

	private bool $degradeToGeneralArray = false;

	/**
	 * @param array<int, ConstantIntegerType|ConstantStringType> $keyTypes
	 * @param array<int, Type> $valueTypes
	 * @param array<int> $optionalKeys
	 * @param int $nextAutoIndex
	 */
	private function __construct(
		array $keyTypes,
		array $valueTypes,
		int $nextAutoIndex,
		array $optionalKeys
	)
	{
		$this->keyTypes = $keyTypes;
		$this->valueTypes = $valueTypes;
		$this->nextAutoIndex = $nextAutoIndex;
		$this->optionalKeys = $optionalKeys;
	}

	public static function createEmpty(): self
	{
		return new self([], [], 0, []);
	}

	public static function createFromConstantArray(ConstantArrayType $startArrayType): self
	{
		return new self(
			$startArrayType->getKeyTypes(),
			$startArrayType->getValueTypes(),
			$startArrayType->getNextAutoIndex(),
			$startArrayType->getOptionalKeys()
		);
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $optional = false): void
	{
		if ($offsetType === null) {
			$offsetType = new ConstantIntegerType($this->nextAutoIndex);
		} else {
			$offsetType = ArrayType::castToArrayKeyType($offsetType);
		}

		if (
			!$this->degradeToGeneralArray
			&& ($offsetType instanceof ConstantIntegerType || $offsetType instanceof ConstantStringType)
		) {
			/** @var ConstantIntegerType|ConstantStringType $keyType */
			foreach ($this->keyTypes as $i => $keyType) {
				if ($keyType->getValue() === $offsetType->getValue()) {
					$this->valueTypes[$i] = $valueType;
					$this->optionalKeys = array_values(array_filter($this->optionalKeys, static function (int $index) use ($i): bool {
						return $index !== $i;
					}));
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
			return;
		}

		$this->keyTypes[] = TypeUtils::generalizeType($offsetType, GeneralizePrecision::moreSpecific());
		$this->valueTypes[] = $valueType;
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
			TypeCombinator::union(...$this->valueTypes)
		);

		if (count($this->optionalKeys) < $keyTypesCount) {
			return TypeCombinator::intersect($array, new NonEmptyArrayType());
		}

		return $array;
	}

}
