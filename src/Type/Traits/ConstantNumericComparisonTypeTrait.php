<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

trait ConstantNumericComparisonTypeTrait
{

	public function getSmallerType(): Type
	{
		$subtractedTypes = [
			new ConstantBooleanType(true),
			IntegerRangeType::createAllGreaterThanOrEqualTo($this->value),
		];

		if (!(bool) $this->value) {
			$subtractedTypes[] = new NullType();
			$subtractedTypes[] = new ConstantBooleanType(false);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getSmallerOrEqualType(): Type
	{
		$subtractedTypes = [
			IntegerRangeType::createAllGreaterThan($this->value),
		];

		if (!(bool) $this->value) {
			$subtractedTypes[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getGreaterType(): Type
	{
		$subtractedTypes = [
			new NullType(),
			new ConstantBooleanType(false),
			IntegerRangeType::createAllSmallerThanOrEqualTo($this->value),
		];

		if ((bool) $this->value) {
			$subtractedTypes[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getGreaterOrEqualType(): Type
	{
		$subtractedTypes = [
			IntegerRangeType::createAllSmallerThan($this->value),
		];

		if ((bool) $this->value) {
			$subtractedTypes[] = new NullType();
			$subtractedTypes[] = new ConstantBooleanType(false);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

}
