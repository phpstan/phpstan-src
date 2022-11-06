<?php declare(strict_types = 1);

namespace PHPStan\Type\Accessory;

use PHPStan\TrinaryLogic;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Traits\MaybeArrayTypeTrait;
use PHPStan\Type\Traits\MaybeCallableTypeTrait;
use PHPStan\Type\Traits\MaybeIterableTypeTrait;
use PHPStan\Type\Traits\MaybeObjectTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\TruthyBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

class HasOffsetType implements CompoundType, AccessoryType
{

	use MaybeArrayTypeTrait;
	use MaybeCallableTypeTrait;
	use MaybeIterableTypeTrait;
	use MaybeObjectTypeTrait;
	use TruthyBooleanTypeTrait;
	use NonGenericTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;
	use NonRemoveableTypeTrait;
	use NonGeneralizableTypeTrait;

	/**
	 * @api
	 * @param ConstantStringType|ConstantIntegerType $offsetType
	 */
	public function __construct(private Type $offsetType)
	{
	}

	/**
	 * @return ConstantStringType|ConstantIntegerType
	 */
	public function getOffsetType(): Type
	{
		return $this->offsetType;
	}

	public function getReferencedClasses(): array
	{
		return [];
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		return $type->isOffsetAccessible()
			->and($type->hasOffsetValueType($this->offsetType));
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($this->equals($type)) {
			return TrinaryLogic::createYes();
		}
		return $type->isOffsetAccessible()
			->and($type->hasOffsetValueType($this->offsetType));
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof UnionType || $otherType instanceof IntersectionType) {
			return $otherType->isSuperTypeOf($this);
		}

		return $otherType->isOffsetAccessible()
			->and($otherType->hasOffsetValueType($this->offsetType))
			->and($otherType instanceof self ? TrinaryLogic::createYes() : TrinaryLogic::createMaybe());
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		return $this->isSubTypeOf($acceptingType);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $this->offsetType->equals($type->offsetType);
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('hasOffset(%s)', $this->offsetType->describe($level));
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		if ($offsetType instanceof ConstantScalarType && $offsetType->equals($this->offsetType)) {
			return TrinaryLogic::createYes();
		}

		return TrinaryLogic::createMaybe();
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		return new MixedType();
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		return $this;
	}

	public function unsetOffset(Type $offsetType): Type
	{
		if ($this->offsetType->isSuperTypeOf($offsetType)->yes()) {
			return new ErrorType();
		}
		return $this;
	}

	public function fillKeysArray(Type $valueType): Type
	{
		return new NonEmptyArrayType();
	}

	public function intersectKeyArray(Type $otherArraysType): Type
	{
		if ($otherArraysType->hasOffsetValueType($this->offsetType)->yes()) {
			return $this;
		}

		return new MixedType();
	}

	public function shuffleArray(): Type
	{
		return new NonEmptyArrayType();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isList(): TrinaryLogic
	{
		if ($this->offsetType->isString()->yes()) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createMaybe();
	}

	public function isBoolean(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isInteger(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isNumericString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isClassStringType(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getKeysArray(): Type
	{
		return new NonEmptyArrayType();
	}

	public function getValuesArray(): Type
	{
		return new NonEmptyArrayType();
	}

	public function toNumber(): Type
	{
		return new ErrorType();
	}

	public function toInteger(): Type
	{
		return new ErrorType();
	}

	public function toFloat(): Type
	{
		return new ErrorType();
	}

	public function toString(): Type
	{
		return new ErrorType();
	}

	public function toArray(): Type
	{
		return new MixedType();
	}

	public function toArrayKey(): Type
	{
		return new ErrorType();
	}

	public function traverse(callable $cb): Type
	{
		return $this;
	}

	public static function __set_state(array $properties): Type
	{
		return new self($properties['offsetType']);
	}

}
