<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Php\PhpVersion;
use PHPStan\Php\PhpVersionFactory;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NumberSet;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\ValueSet;
use function count;

trait OperatorTypeTrait
{

	public function exponentiate(Type $exponent): Type
	{
		return $this->arithmeticPower($exponent, new PhpVersion(PhpVersionFactory::MAX_PHP_VERSION));
	}

	public function toNumberSet(): ?NumberSet
	{
		return null;
	}

	public function toValueSet(): ValueSet
	{
		return ValueSet::createUnknown();
	}

	public function arithmeticAdd(Type $right, PhpVersion $phpVersion): Type
	{
		$leftValues = $this->toValueSet();
		$rightValues = $right->toValueSet();
		if (!$leftValues->isEmpty() && !$rightValues->isEmpty() && $this->isArray()->yes() && $right->isArray()->yes()) {
			return $this->arrayUnion($right, $phpVersion);
		}

		// strings, numbers, booleans and arrays added to each other give each of them,
		// as the operand types themselves to keep what else they tell
		if ($this->isArray()->maybe() && $right->isArray()->maybe()) {
			$leftIsPlusable = $leftValues->isPlusable();
			$rightIsPlusable = $rightValues->isPlusable();
			if ($leftIsPlusable && $rightIsPlusable) {
				return TypeCombinator::union($this, $right);
			}
			if ($leftIsPlusable && $rightValues->isUnknown()) {
				return $this;
			}
			if ($rightIsPlusable && $leftValues->isUnknown()) {
				return $right;
			}
		}

		return $leftValues->arithmeticAdd($rightValues, $phpVersion);
	}

	public function arithmeticSubtract(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->arithmeticSubtract($right->toValueSet(), $phpVersion);
	}

	public function arithmeticMultiply(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->arithmeticMultiply($right->toValueSet(), $phpVersion);
	}

	public function arithmeticDivide(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->arithmeticDivide($right->toValueSet(), $phpVersion);
	}

	public function arithmeticIntDivide(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toInteger()->toValueSet()->arithmeticIntDivide($right->toInteger()->toValueSet(), $phpVersion);
	}

	public function arithmeticModulo(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->arithmeticModulo($right->toValueSet(), $phpVersion);
	}

	public function arithmeticNegate(PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->arithmeticNegate($phpVersion);
	}

	public function bitwiseAnd(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->bitwiseAnd($right->toValueSet(), $phpVersion);
	}

	public function bitwiseOr(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->bitwiseOr($right->toValueSet(), $phpVersion);
	}

	public function bitwiseXor(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->bitwiseXor($right->toValueSet(), $phpVersion);
	}

	public function bitwiseShiftLeft(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->bitwiseShiftLeft($right->toValueSet(), $phpVersion);
	}

	public function bitwiseShiftRight(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->bitwiseShiftRight($right->toValueSet(), $phpVersion);
	}

	public function getPossiblyLooselyEqualType(PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->getPossiblyLooselyEqualType($phpVersion);
	}

	public function getAlwaysLooselyEqualType(PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->getAlwaysLooselyEqualType($phpVersion);
	}

	public function stringConcatenate(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->stringConcatenate($right->toValueSet(), $phpVersion);
	}

	public function arrayUnion(Type $right, PhpVersion $phpVersion): Type
	{
		$leftConstantArrays = $this->getConstantArrays();
		$rightConstantArrays = $right->getConstantArrays();

		$leftCount = count($leftConstantArrays);
		$rightCount = count($rightConstantArrays);
		if ($leftCount > 0 && $rightCount > 0
			&& ($leftCount + $rightCount < ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT)) {
			$resultTypes = [];
			foreach ($rightConstantArrays as $rightConstantArray) {
				foreach ($leftConstantArrays as $leftConstantArray) {
					$newArrayBuilder = ConstantArrayTypeBuilder::createFromConstantArray($rightConstantArray);
					foreach ($leftConstantArray->getKeyTypes() as $i => $leftKeyType) {
						$optional = $leftConstantArray->isOptionalKey($i);
						$valueType = $leftConstantArray->getOffsetValueType($leftKeyType);
						if (!$optional) {
							if ($rightConstantArray->hasOffsetValueType($leftKeyType)->maybe()) {
								$valueType = TypeCombinator::union($valueType, $rightConstantArray->getOffsetValueType($leftKeyType));
							}
						}
						$newArrayBuilder->setOffsetValueType(
							$leftKeyType,
							$valueType,
							$optional,
						);
					}
					$resultTypes[] = $newArrayBuilder->getArray();
				}
			}
			return TypeCombinator::union(...$resultTypes);
		}

		// `array{...} + array<TKey, TValue>` keeps the known keyed prefix and
		// folds the right-hand side into an open `...<TKey, TValue>` tail. This
		// only applies when the left arrays' sealedness is known (bleeding edge);
		// otherwise the legacy behavior below is kept for backward compatibility.
		if ($leftCount > 0 && $rightCount === 0
			&& $leftCount < ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT
			&& $right->isArray()->yes()) {
			$allSealednessKnown = true;
			foreach ($leftConstantArrays as $leftConstantArray) {
				if ($leftConstantArray->isUnsealed()->maybe()) {
					$allSealednessKnown = false;
					break;
				}
			}

			if ($allSealednessKnown) {
				$rightKeyType = $right->getIterableKeyType();
				$rightValueType = $right->getIterableValueType();
				$resultTypes = [];
				foreach ($leftConstantArrays as $leftConstantArray) {
					$newArrayBuilder = ConstantArrayTypeBuilder::createFromConstantArray($leftConstantArray);
					$existingUnsealed = $leftConstantArray->getUnsealedTypes();
					if ($leftConstantArray->isUnsealed()->yes() && $existingUnsealed !== null) {
						$newArrayBuilder->makeUnsealed(
							TypeCombinator::union($existingUnsealed[0], $rightKeyType),
							TypeCombinator::union($existingUnsealed[1], $rightValueType),
						);
					} else {
						$newArrayBuilder->makeUnsealed($rightKeyType, $rightValueType);
					}
					$resultTypes[] = $newArrayBuilder->getArray();
				}
				return TypeCombinator::union(...$resultTypes);
			}
		}

		$leftIsArray = $this->isArray();
		$rightIsArray = $right->isArray();
		if ($leftIsArray->yes() && $rightIsArray->yes()) {
			if ($this->getIterableKeyType()->equals($right->getIterableKeyType())) {
				// to preserve BenevolentUnionType
				$keyType = $this->getIterableKeyType();
			} else {
				$keyTypes = [];
				foreach ([
					$this->getIterableKeyType(),
					$right->getIterableKeyType(),
				] as $keyType) {
					$keyTypes[] = $keyType;
				}
				$keyType = TypeCombinator::union(...$keyTypes);
			}

			$leftIterableValueType = $this->getIterableValueType();
			$arrayType = new ArrayType(
				$keyType,
				TypeCombinator::union($leftIterableValueType, $right->getIterableValueType()),
			);

			$accessories = [];
			if ($leftCount > 0) {
				// Use the first constant array as a reference to list potential offsets.
				// We only need to check the first array because we're looking for offsets that exist in ALL arrays.
				$constantArray = $leftConstantArrays[0];
				foreach ($constantArray->getKeyTypes() as $offsetType) {
					if (!$this->hasOffsetValueType($offsetType)->yes()) {
						continue;
					}

					$valueType = $this->getOffsetValueType($offsetType);
					$accessories[] = new HasOffsetValueType($offsetType, $valueType);
				}
			}

			if ($rightCount > 0) {
				// Use the first constant array as a reference to list potential offsets.
				// We only need to check the first array because we're looking for offsets that exist in ALL arrays.
				$constantArray = $rightConstantArrays[0];
				foreach ($constantArray->getKeyTypes() as $offsetType) {
					if (!$right->hasOffsetValueType($offsetType)->yes()) {
						continue;
					}

					$valueType = TypeCombinator::union($leftIterableValueType, $right->getOffsetValueType($offsetType));
					$accessories[] = new HasOffsetValueType($offsetType, $valueType);
				}
			}

			if ($this->isIterableAtLeastOnce()->yes() || $right->isIterableAtLeastOnce()->yes()) {
				$accessories[] = new NonEmptyArrayType();
			}
			if ($this->isList()->yes() && $right->isList()->yes()) {
				$accessories[] = new AccessoryArrayListType();
			}

			if (count($accessories) > 0) {
				$arrayType = TypeCombinator::intersect($arrayType, ...$accessories);
			}

			return $arrayType;
		}

		return new ErrorType();
	}

}
