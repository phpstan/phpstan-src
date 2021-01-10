<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

trait UndecidedComparisonTypeTrait
{

	public function isSmallerThan(Type $otherType): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getSmallerType(): Type
	{
		return new MixedType();
	}

	public function getSmallerOrEqualType(): Type
	{
		return new MixedType();
	}

	public function getGreaterType(): Type
	{
		return new MixedType();
	}

	public function getGreaterOrEqualType(): Type
	{
		return new MixedType();
	}

}
