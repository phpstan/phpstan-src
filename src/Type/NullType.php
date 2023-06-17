<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Traits\FalseyBooleanTypeTrait;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonCallableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\NonRemovableTypeTrait;

/** @api */
class NullType implements ConstantScalarType
{

	use NonArrayTypeTrait;
	use NonCallableTypeTrait;
	use NonIterableTypeTrait;
	use NonObjectTypeTrait;
	use FalseyBooleanTypeTrait;
	use NonGenericTypeTrait;
	use NonRemovableTypeTrait;

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

	public function getObjectClassNames(): array
	{
		return [];
	}

	public function getObjectClassReflections(): array
	{
		return [];
	}

	public function getConstantStrings(): array
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

	public function generalize(GeneralizePrecision $precision): Type
	{
		return $this;
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->acceptsWithReason($type, $strictTypes)->result;
	}

	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof self) {
			return AcceptsResult::createYes();
		}

		if ($type instanceof CompoundType) {
			return $type->isAcceptedWithReasonBy($this, $strictTypes);
		}

		return AcceptsResult::createNo();
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

	public function toArrayKey(): Type
	{
		return new ConstantStringType('');
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

	public function unsetOffset(Type $offsetType): Type
	{
		return $this;
	}

	public function traverse(callable $cb): Type
	{
		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		return $this;
	}

	public function isNull(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isConstantValue(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isConstantScalarValue(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function getConstantScalarTypes(): array
	{
		return [$this];
	}

	public function getConstantScalarValues(): array
	{
		return [$this->getValue()];
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

	public function isNonFalsyString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isClassStringType(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getClassStringObjectType(): Type
	{
		return new ErrorType();
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return new ErrorType();
	}

	public function isVoid(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isScalar(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		if ($type instanceof ConstantScalarType) {
			return LooseComparisonHelper::compareConstantScalars($this, $type, $phpVersion);
		}

		if ($type->isConstantArray()->yes() && $type->isIterableAtLeastOnce()->no()) {
			// @phpstan-ignore-next-line
			return new ConstantBooleanType($this->getValue() == []); // phpcs:ignore
		}

		return new BooleanType();
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

	public function exponentiate(Type $exponent): Type
	{
		return new UnionType(
			[
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			],
		);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode('null');
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self();
	}

}
