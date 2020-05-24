<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\TrinaryLogic;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class ConstantNumericStringType extends ConstantIntegerType
{

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			return $this->getValue() === $type->getValue() ? TrinaryLogic::createYes() : TrinaryLogic::createNo();
		}

		if ($type instanceof ConstantStringType) {
			return (string) $this->getValue() === $type->getValue() ? TrinaryLogic::createYes() : TrinaryLogic::createNo();
		}

		if ($type instanceof StringType) {
			return TrinaryLogic::createMaybe();
		}

		return parent::isSuperTypeOf($type);
	}

}
