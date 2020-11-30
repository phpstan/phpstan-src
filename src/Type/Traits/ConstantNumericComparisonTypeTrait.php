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

	public function getSmallerType(bool $orEqual = false): Type
	{
		$subtractedTypes = [
			IntegerRangeType::createAllGreaterThan($this->value, !$orEqual),
		];

		$boolValue = (bool) $this->value;
		if (!$boolValue && !$orEqual) {
			$subtractedTypes[] = new NullType();
			$subtractedTypes[] = new ConstantBooleanType(false);
		}
		if (!$boolValue || !$orEqual) {
			$subtractedTypes[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getGreaterType(bool $orEqual = false): Type
	{
		$subtractedTypes = [
			IntegerRangeType::createAllSmallerThan($this->value, !$orEqual),
		];

		$boolValue = (bool) $this->value;
		if ($boolValue || !$orEqual) {
			$subtractedTypes[] = new NullType();
			$subtractedTypes[] = new ConstantBooleanType(false);
		}
		if ($boolValue && !$orEqual) {
			$subtractedTypes[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

}
