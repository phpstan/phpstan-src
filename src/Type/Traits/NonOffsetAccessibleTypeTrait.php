<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\TrinaryLogic;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

trait NonOffsetAccessibleTypeTrait
{

	public function isOffsetAccessible(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		return new ErrorType();
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		return new ErrorType();
	}

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		return new ErrorType();
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return new ErrorType();
	}

}
