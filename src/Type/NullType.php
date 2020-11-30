<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Traits\FalseyBooleanTypeTrait;
use PHPStan\Type\Traits\NonCallableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;

class NullType implements ConstantScalarType
{

	use NonCallableTypeTrait;
	use NonIterableTypeTrait;
	use NonObjectTypeTrait;
	use FalseyBooleanTypeTrait;
	use NonGenericTypeTrait;

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return [];
	}

	/**
	 * @return null
	 */
	public function getValue()
	{
		return null;
	}

	public function generalize(): Type
	{
		return $this;
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof self) {
			return TrinaryLogic::createYes();
		}

		if ($type instanceof CompoundType) {
			return CompoundTypeHelper::accepts($type, $this, $strictTypes);
		}

		return TrinaryLogic::createNo();
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			return TrinaryLogic::createYes();
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return TrinaryLogic::createNo();
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self;
	}

	public function isSmallerThan(Type $otherType, bool $orEqual = false): TrinaryLogic
	{
		if ($otherType instanceof ConstantScalarType) {
			if ($orEqual) {
				return TrinaryLogic::createFromBoolean(null <= $otherType->getValue());
			}
			return TrinaryLogic::createFromBoolean(null < $otherType->getValue());
		}

		if ($otherType instanceof CompoundType) {
			return $otherType->isGreaterThan($this, $orEqual);
		}

		return TrinaryLogic::createMaybe();
	}

	public function describe(VerbosityLevel $level): string
	{
		return 'null';
	}

	public function toNumber(): Type
	{
		return new ConstantIntegerType(0);
	}

	public function toString(): Type
	{
		return new ConstantStringType('');
	}

	public function toInteger(): Type
	{
		return $this->toNumber();
	}

	public function toFloat(): Type
	{
		return $this->toNumber()->toFloat();
	}

	public function toArray(): Type
	{
		return new ConstantArrayType([], []);
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		return new ErrorType();
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType): Type
	{
		$array = new ConstantArrayType([], []);
		return $array->setOffsetValueType($offsetType, $valueType);
	}

	public function traverse(callable $cb): Type
	{
		return $this;
	}

	public function isArray(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNumericString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getSmallerType(bool $orEqual = false): Type
	{
		if ($orEqual) {
			// All falsey types except '0'
			return new UnionType([
				new NullType(),
				new ConstantBooleanType(false),
				new ConstantIntegerType(0),
				new ConstantFloatType(0.0),
				new ConstantStringType(''),
				new ConstantArrayType([], []),
			]);
		}

		return new NeverType();
	}

	public function getGreaterType(bool $orEqual = false): Type
	{
		if ($orEqual) {
			return new MixedType();
		}

		// All truthy types, but also '0'
		return new MixedType(false, new UnionType([
			new NullType(),
			new ConstantBooleanType(false),
			new ConstantIntegerType(0),
			new ConstantFloatType(0.0),
			new ConstantStringType(''),
			new ConstantArrayType([], []),
		]));
	}

	/**
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		return new self();
	}

}
