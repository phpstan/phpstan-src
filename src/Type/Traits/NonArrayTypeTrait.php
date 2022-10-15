<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\TrinaryLogic;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

trait NonArrayTypeTrait
{

	public function getArrays(): array
	{
		return [];
	}

	public function getConstantArrays(): array
	{
		return [];
	}

	public function isArray(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isConstantArray(): TrinaryLogic
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

	public function getKeysArray(): Type
	{
		return new ErrorType();
	}

	public function getValuesArray(): Type
	{
		return new ErrorType();
	}

	public function flipArray(): Type
	{
		return new ErrorType();
	}

}
