<?php declare(strict_types = 1);

namespace PHPStan\Type\Accessory;

use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
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

class HasOffsetValueType implements CompoundType, AccessoryType
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

	public function __construct(private ConstantStringType|ConstantIntegerType $offsetType, private Type $valueType)
	{
	}

	public function getOffsetType(): ConstantStringType|ConstantIntegerType
	{
		return $this->offsetType;
	}

	public function getValueType(): Type
	{
		return $this->valueType;
	}

	public function getReferencedClasses(): array
	{
		return [];
	}

	public function getObjectClassNames(): array
	{
		return [];
	}

	public function getConstantStrings(): array
	{
		return [];
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->acceptsWithReason($type, $strictTypes)->result;
	}

	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedWithReasonBy($this, $strictTypes);
		}

		return new AcceptsResult(
			$type->isOffsetAccessible()
				->and($type->hasOffsetValueType($this->offsetType))
				->and($this->valueType->accepts($type->getOffsetValueType($this->offsetType), $strictTypes)),
			[],
		);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($this->equals($type)) {
			return TrinaryLogic::createYes();
		}
		return $type->isOffsetAccessible()
			->and($type->hasOffsetValueType($this->offsetType))
			->and($this->valueType->isSuperTypeOf($type->getOffsetValueType($this->offsetType)));
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof UnionType || $otherType instanceof IntersectionType) {
			return $otherType->isSuperTypeOf($this);
		}

		return $otherType->isOffsetAccessible()
			->and($otherType->hasOffsetValueType($this->offsetType))
			->and($otherType->getOffsetValueType($this->offsetType)->isSuperTypeOf($this->valueType))
			->and($otherType instanceof self ? TrinaryLogic::createYes() : TrinaryLogic::createMaybe());
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		return $this->isAcceptedWithReasonBy($acceptingType, $strictTypes)->result;
	}

	public function isAcceptedWithReasonBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		return new AcceptsResult($this->isSubTypeOf($acceptingType), []);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $this->offsetType->equals($type->offsetType)
			&& $this->valueType->equals($type->valueType);
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('hasOffsetValue(%s, %s)', $this->offsetType->describe($level), $this->valueType->describe($level));
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
		if ($offsetType instanceof ConstantScalarType && $offsetType->equals($this->offsetType)) {
			return $this->valueType;
		}

		return new MixedType();
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		if ($offsetType === null) {
			return $this;
		}

		if (!$offsetType->equals($this->offsetType)) {
			return $this;
		}

		if (!$offsetType instanceof ConstantIntegerType && !$offsetType instanceof ConstantStringType) {
			throw new ShouldNotHappenException();
		}

		return new self($offsetType, $valueType);
	}

	public function unsetOffset(Type $offsetType): Type
	{
		if ($this->offsetType->isSuperTypeOf($offsetType)->yes()) {
			return new ErrorType();
		}
		return $this;
	}

	public function getKeysArray(): Type
	{
		return new NonEmptyArrayType();
	}

	public function getValuesArray(): Type
	{
		return new NonEmptyArrayType();
	}

	public function fillKeysArray(Type $valueType): Type
	{
		return new NonEmptyArrayType();
	}

	public function flipArray(): Type
	{
		$valueType = $this->valueType->toArrayKey();
		if ($valueType instanceof ConstantIntegerType || $valueType instanceof ConstantStringType) {
			return new self($valueType, $this->offsetType);
		}

		return new MixedType();
	}

	public function intersectKeyArray(Type $otherArraysType): Type
	{
		if ($otherArraysType->hasOffsetValueType($this->offsetType)->yes()) {
			return $this;
		}

		return new MixedType();
	}

	public function searchArray(Type $needleType): Type
	{
		if (
			$needleType instanceof ConstantScalarType && $this->valueType instanceof ConstantScalarType
			&& $needleType->getValue() === $this->valueType->getValue()
		) {
			return $this->offsetType;
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

	public function isNull(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isTrue(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFalse(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isBoolean(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFloat(): TrinaryLogic
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

	public function isVoid(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isScalar(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
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

	public function getEnumCases(): array
	{
		return [];
	}

	public function traverse(callable $cb): Type
	{
		$newValueType = $cb($this->valueType);
		if ($newValueType === $this->valueType) {
			return $this;
		}

		return new self($this->offsetType, $newValueType);
	}

	public function exponentiate(Type $exponent): Type
	{
		return new ErrorType();
	}

	public static function __set_state(array $properties): Type
	{
		return new self($properties['offsetType'], $properties['valueType']);
	}

}
