<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function count;
use function range;

/** @api */
class ConstantArrayTypeBuilder
{

	public const ARRAY_COUNT_LIMIT = 256;

	/** @var list<ConstantArrayTypeBuilderInnards> */
	private array $innards;

	/**
	 * @param array<int, Type> $keyTypes
	 * @param array<int, Type> $valueTypes
	 * @param non-empty-list<int> $nextAutoIndexes
	 * @param array<int> $optionalKeys
	 */
	private function __construct(
		array $keyTypes,
		array $valueTypes,
		array $nextAutoIndexes,
		array $optionalKeys,
		TrinaryLogic $isList,
	)
	{
		$this->innards = [new ConstantArrayTypeBuilderInnards($keyTypes, $valueTypes, $nextAutoIndexes, $optionalKeys, $isList)];
	}

	public static function createEmpty(): self
	{
		return new self([], [], [0], [], TrinaryLogic::createYes());
	}

	public static function createFromConstantArray(ConstantArrayType $startArrayType): self
	{
		$builder = new self(
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

	private function isAtLeastOneInnardDegraded(): bool
	{
		foreach ($this->innards as $innard) {
			if (!$innard->degradeToGeneralArray) {
				continue;
			}

			return true;
		}

		return false;
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $optional = false): void
	{
		if ($offsetType !== null) {
			$offsetType = $offsetType->toArrayKey();
		}

		if (!$this->isAtLeastOneInnardDegraded()) {
			if ($offsetType === null) {
				foreach ($this->innards as $innard) {
					$innard->setValueTypeForNewOffset($valueType, $optional);
				}
				return;
			}

			if ($offsetType instanceof ConstantIntegerType || $offsetType instanceof ConstantStringType) {
				foreach ($this->innards as $innard) {
					$innard->setValueTypeForConstantOffset($offsetType, $valueType, $optional);
				}
				return;
			}

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
			if (
				count($scalarTypes) > 0
				&& (count($scalarTypes) * count($this->innards)) < self::ARRAY_COUNT_LIMIT
			) {
				$newInnards = [];
				foreach ($scalarTypes as $scalarType) {
					foreach ($this->innards as $innard) {
						$scalarOffsetType = $scalarType->toArrayKey();
						if (!$scalarOffsetType instanceof ConstantIntegerType && !$scalarOffsetType instanceof ConstantStringType) {
							throw new ShouldNotHappenException();
						}

						$newInnard = $innard->duplicate();
						$newInnard->setValueTypeForConstantOffset($scalarOffsetType, $valueType, $optional);
						$newInnards[] = $newInnard;
					}
				}

				$this->innards = $newInnards;

				return;
			}
		}

		foreach ($this->innards as $innard) {
			$innard->setValueTypeForGeneralOffset($offsetType, $valueType, $optional);
		}
	}

	public function degradeToGeneralArray(bool $oversized = false): void
	{
		foreach ($this->innards as $innard) {
			$innard->degradeToGeneralArray($oversized);
		}
	}

	public function getArray(): Type
	{
		$arrays = [];
		foreach ($this->innards as $innard) {
			$arrays[] = $innard->getArray();
		}

		return TypeCombinator::union(...$arrays);
	}

	public function isList(): bool
	{
		return TrinaryLogic::lazyExtremeIdentity($this->innards, static fn (ConstantArrayTypeBuilderInnards $innard) => $innard->isList)->yes();
	}

}
