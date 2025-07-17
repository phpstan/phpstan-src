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

	public function getKeysArrayFiltered(Type $filterValueType, TrinaryLogic $strict): Type
	{
		return $this->getKeysArray();
	}

	public function getKeysArray(): Type
	{
		return new ErrorType();
	}

	public function getValuesArray(): Type
	{
		return new ErrorType();
	}

	public function chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		return new ErrorType();
	}

	public function fillKeysArray(Type $valueType): Type
	{
		return new ErrorType();
	}

	public function flipArray(): Type
	{
		return new ErrorType();
	}

	public function intersectKeyArray(Type $otherArraysType): Type
	{
		return new ErrorType();
	}

	public function popArray(): Type
	{
		return new ErrorType();
	}

	public function reverseArray(TrinaryLogic $preserveKeys): Type
	{
		return new ErrorType();
	}

	public function searchArray(Type $needleType, ?TrinaryLogic $strict = null): Type
	{
		return new ErrorType();
	}

	public function shiftArray(): Type
	{
		return new ErrorType();
	}

	public function shuffleArray(): Type
	{
		return new ErrorType();
	}

	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		return new ErrorType();
	}

	public function spliceArray(Type $offsetType, Type $lengthType, Type $replacementType): Type
	{
		return new ErrorType();
	}

}
