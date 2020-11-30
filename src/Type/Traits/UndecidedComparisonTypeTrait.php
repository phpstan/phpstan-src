<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

trait UndecidedComparisonTypeTrait
{

	public function isSmallerThan(Type $otherType, bool $orEqual = false): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getSmallerType(bool $orEqual = false): Type
	{
		return new MixedType();
	}

	public function getGreaterType(bool $orEqual = false): Type
	{
		return new MixedType();
	}

}
