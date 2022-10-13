<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\TrinaryLogic;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

trait MaybeIterableTypeTrait
{

	public function isIterable(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getArraySize(): Type
	{
		if ($this->isIterable()->no()) {
			return new ErrorType();
		}

		if ($this->isIterableAtLeastOnce()->yes()) {
			return IntegerRangeType::fromInterval(1, null);
		}

		return IntegerRangeType::fromInterval(0, null);
	}

	public function getIterableKeyType(): Type
	{
		return new MixedType();
	}

	public function getFirstIterableKeyType(): Type
	{
		return new MixedType();
	}

	public function getLastIterableKeyType(): Type
	{
		return new MixedType();
	}

	public function getIterableValueType(): Type
	{
		return new MixedType();
	}

	public function getFirstIterableValueType(): Type
	{
		return new MixedType();
	}

	public function getLastIterableValueType(): Type
	{
		return new MixedType();
	}

}
