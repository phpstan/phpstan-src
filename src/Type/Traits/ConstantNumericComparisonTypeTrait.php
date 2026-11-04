<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Php\PhpVersion;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\FloatRangeType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use const NAN;

trait ConstantNumericComparisonTypeTrait
{

	public function getSmallerType(PhpVersion $phpVersion): Type
	{
		$subtractedTypes = [
			new ConstantBooleanType(true),
			new ConstantFloatType(NAN),
			IntegerRangeType::createAllGreaterThanOrEqualTo($this->value),
			FloatRangeType::createAllGreaterThanOrEqualTo($this->value),
		];

		if ($this->toBoolean()->isFalse()->yes()) {
			$subtractedTypes[] = new NullType();
			$subtractedTypes[] = new ConstantBooleanType(false);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getSmallerOrEqualType(PhpVersion $phpVersion): Type
	{
		$subtractedTypes = [
			new ConstantFloatType(NAN),
			IntegerRangeType::createAllGreaterThan($this->value),
			FloatRangeType::createAllGreaterThan($this->value),
		];

		if ($this->toBoolean()->isFalse()->yes()) {
			$subtractedTypes[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getGreaterType(PhpVersion $phpVersion): Type
	{
		$subtractedTypes = [
			new NullType(),
			new ConstantBooleanType(false),
			new ConstantFloatType(NAN),
			IntegerRangeType::createAllSmallerThanOrEqualTo($this->value),
			FloatRangeType::createAllSmallerThanOrEqualTo($this->value),
		];

		if (!$this->toBoolean()->isFalse()->yes()) {
			$subtractedTypes[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getGreaterOrEqualType(PhpVersion $phpVersion): Type
	{
		$subtractedTypes = [
			new ConstantFloatType(NAN),
			IntegerRangeType::createAllSmallerThan($this->value),
			FloatRangeType::createAllSmallerThan($this->value),
		];

		if (!$this->toBoolean()->isFalse()->yes()) {
			$subtractedTypes[] = new NullType();
			$subtractedTypes[] = new ConstantBooleanType(false);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

}
