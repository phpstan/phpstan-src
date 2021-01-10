<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

trait UndecidedComparisonCompoundTypeTrait
{

	use UndecidedComparisonTypeTrait;

	public function isGreaterThan(Type $otherType): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isGreaterThanOrEqual(Type $otherType): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

}
