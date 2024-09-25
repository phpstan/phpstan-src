<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Php\PhpVersion;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

trait UndecidedComparisonTypeTrait
{

	public function isSmallerThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isSmallerThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getSmallerType(PhpVersion $phpVersion): Type
	{
		return new MixedType();
	}

	public function getSmallerOrEqualType(PhpVersion $phpVersion): Type
	{
		return new MixedType();
	}

	public function getGreaterType(PhpVersion $phpVersion): Type
	{
		return new MixedType();
	}

	public function getGreaterOrEqualType(PhpVersion $phpVersion): Type
	{
		return new MixedType();
	}

}
