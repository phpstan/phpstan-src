<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\TrinaryLogic;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

trait NonIterableTypeTrait
{

	public function isIterable(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getIterableCount(): Type
	{
		return new ErrorType();
	}

	public function getIterableKeyType(): Type
	{
		return new ErrorType();
	}

	public function getFirstIterableKeyType(): Type
	{
		return new ErrorType();
	}

	public function getLastIterableKeyType(): Type
	{
		return new ErrorType();
	}

	public function getIterableValueType(): Type
	{
		return new ErrorType();
	}

	public function getFirstIterableValueType(): Type
	{
		return new ErrorType();
	}

	public function getLastIterableValueType(): Type
	{
		return new ErrorType();
	}

}
