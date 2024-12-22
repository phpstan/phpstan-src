<?php declare(strict_types = 1);

namespace PHPStan\Type\Accessory;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Traits\MaybeCallableTypeTrait;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

class AccessoryNonEmptyStringType implements CompoundType, AccessoryType
{

	use MaybeCallableTypeTrait;
	use NonArrayTypeTrait;
	use NonObjectTypeTrait;
	use NonIterableTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;
	use NonGenericTypeTrait;
	use UndecidedBooleanTypeTrait;

	/** @api */
	public function __construct()
	{
	}

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

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		return new AcceptsResult($type->isNonEmptyString(), []);
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		if ($this->equals($type)) {
			return IsSuperTypeOfResult::createYes();
		}

		if ($type->isNonFalsyString()->yes()) {
			return IsSuperTypeOfResult::createYes();
		}

		return new IsSuperTypeOfResult($type->isNonEmptyString(), []);
	}

	public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult
	{
		if ($otherType instanceof UnionType || $otherType instanceof IntersectionType) {
			return $otherType->isSuperTypeOf($this);
		}

		return (new IsSuperTypeOfResult($otherType->isNonEmptyString(), []))
			->and($otherType instanceof self ? IsSuperTypeOfResult::createYes() : IsSuperTypeOfResult::createMaybe());
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		return $this->isSubTypeOf($acceptingType)->toAcceptsResult();
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self;
	}

	public function describe(VerbosityLevel $level): string
	{
		return 'non-empty-string';
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isOffsetAccessLegal(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return $offsetType->isInteger()->and(TrinaryLogic::createMaybe());
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		if ($this->hasOffsetValueType($offsetType)->no()) {
			return new ErrorType();
		}

		if ((new ConstantIntegerType(0))->isSuperTypeOf($offsetType)->yes()) {
			return new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]);
		}

		return new StringType();
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		$stringOffset = (new StringType())->setOffsetValueType($offsetType, $valueType, $unionValues);

		if ($stringOffset instanceof ErrorType) {
			return $stringOffset;
		}

		return $this;
	}

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		return $this;
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return new ErrorType();
	}

	public function toNumber(): Type
	{
		return new ErrorType();
	}

	public function toAbsoluteNumber(): Type
	{
		return new ErrorType();
	}

	public function toInteger(): Type
	{
		return new IntegerType();
	}

	public function toFloat(): Type
	{
		return new FloatType();
	}

	public function toString(): Type
	{
		return $this;
	}

	public function toArray(): Type
	{
		return new ConstantArrayType(
			[new ConstantIntegerType(0)],
			[$this],
			[1],
			[],
			TrinaryLogic::createYes(),
		);
	}

	public function toArrayKey(): Type
	{
		return $this;
	}

	public function isNull(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isConstantValue(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isConstantScalarValue(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getConstantScalarTypes(): array
	{
		return [];
	}

	public function getConstantScalarValues(): array
	{
		return [];
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
		return TrinaryLogic::createYes();
	}

	public function isNumericString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isLowercaseString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isClassString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isUppercaseString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getClassStringObjectType(): Type
	{
		return new ObjectWithoutClassType();
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return new ObjectWithoutClassType();
	}

	public function isVoid(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isScalar(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		if ($type->isString()->yes() && $type->isNonEmptyString()->no()) {
			return new ConstantBooleanType(false);
		}
		return new BooleanType();
	}

	public function traverse(callable $cb): Type
	{
		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		return $this;
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		return new StringType();
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($typeToRemove instanceof ConstantStringType && $typeToRemove->getValue() === '0') {
			return TypeCombinator::intersect($this, new AccessoryNonFalsyStringType());
		}

		return null;
	}

	public function exponentiate(Type $exponent): Type
	{
		return new BenevolentUnionType([
			new FloatType(),
			new IntegerType(),
		]);
	}

	public function getFiniteTypes(): array
	{
		return [];
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode('non-empty-string');
	}

}
