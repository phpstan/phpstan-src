<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Traits\NonCallableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;

class FloatType implements Type
{

	use NonCallableTypeTrait;
	use NonIterableTypeTrait;
	use NonObjectTypeTrait;
	use UndecidedBooleanTypeTrait;
	use UndecidedComparisonTypeTrait;
	use NonGenericTypeTrait;

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return [];
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof self || $type instanceof IntegerType) {
			return TrinaryLogic::createYes();
		}

		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy(new UnionType([
				$this,
				new IntegerType(),
			]), $strictTypes);
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
		return get_class($type) === static::class;
	}

	public function describe(VerbosityLevel $level): string
	{
		return 'float';
	}

	public function toNumber(): Type
	{
		return $this;
	}

	public function toFloat(): Type
	{
		return $this;
	}

	public function toInteger(): Type
	{
		return new IntegerType();
	}

	public function toString(): Type
	{
		return new IntersectionType([
			new StringType(),
			new AccessoryNumericStringType(),
		]);
	}

	public function toArray(): Type
	{
		return new ConstantArrayType(
			[new ConstantIntegerType(0)],
			[$this],
			1
		);
	}

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

	public function setOffsetValueType(?Type $offsetType, Type $valueType): Type
	{
		return new ErrorType();
	}

	public function isArray(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNumericString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function traverse(callable $cb): Type
	{
		return $this;
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
