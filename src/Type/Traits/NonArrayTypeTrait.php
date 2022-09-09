<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\TrinaryLogic;

trait NonArrayTypeTrait
{

	public function isArray(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isOversizedArray(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isList(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

}
