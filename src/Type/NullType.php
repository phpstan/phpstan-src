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

/** @api */
class NullType implements ConstantScalarType
{

	use NonCallableTypeTrait;
	use NonIterableTypeTrait;
	use NonObjectTypeTrait;
	use FalseyBooleanTypeTrait;
	use NonGenericTypeTrait;

	/** @api */
	public function __construct()
	{
	}

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

	public function isSmallerThan(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof ConstantScalarType) {
			return TrinaryLogic::createFromBoolean(null < $otherType->getValue());
		}

		if ($otherType instanceof CompoundType) {
			return $otherType->isGreaterThan($this);
		}

		return TrinaryLogic::createMaybe();
	}

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof ConstantScalarType) {
			return TrinaryLogic::createFromBoolean(null <= $otherType->getValue());
		}

		if ($otherType instanceof CompoundType) {
			return $otherType->isGreaterThanOrEqual($this);
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

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		$array = new ConstantArrayType([], []);
		return $array->setOffsetValueType($offsetType, $valueType, $unionValues);
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

	public function isNonEmptyString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getSmallerType(): Type
	{
		return new NeverType();
	}

	public function getSmallerOrEqualType(): Type
	{
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

	public function getGreaterType(): Type
	{
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

	public function getGreaterOrEqualType(): Type
	{
		return new MixedType();
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
