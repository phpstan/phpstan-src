<?php declare(strict_types = 1);

namespace PHPStan\Type\Accessory;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\MixedType;
use PHPStan\Type\Traits\MaybeCallableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\TruthyBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

class NonEmptyArrayType implements CompoundType, AccessoryType
{

	use MaybeCallableTypeTrait;
	use NonObjectTypeTrait;
	use TruthyBooleanTypeTrait;
	use NonGenericTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;
	use NonRemoveableTypeTrait;
	use NonGeneralizableTypeTrait;

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

	public function getArrays(): array
	{
		return [];
	}

	public function getConstantArrays(): array
	{
		return [];
	}

	public function getConstantStrings(): array
	{
		return [];
	}

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		$isArray = $type->isArray();
		$isIterableAtLeastOnce = $type->isIterableAtLeastOnce();
		$isNonEmptyArray = $isArray->and($isIterableAtLeastOnce);

		if ($isNonEmptyArray->yes()) {
			return AcceptsResult::createYes();
		}

		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		return new AcceptsResult($isNonEmptyArray, []);
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($this->equals($type)) {
			return IsSuperTypeOfResult::createYes();
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return new IsSuperTypeOfResult($type->isArray()->and($type->isIterableAtLeastOnce()), []);
	}

	public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult
	{
		if ($otherType instanceof UnionType || $otherType instanceof IntersectionType) {
			return $otherType->isSuperTypeOf($this);
		}

		return new IsSuperTypeOfResult(
			$otherType->isArray()->and($otherType->isIterableAtLeastOnce())->and($otherType instanceof self ? TrinaryLogic::createYes() : TrinaryLogic::createMaybe()),
			[],
		);
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
		return 'non-empty-array';
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

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		return $this;
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return new ErrorType();
	}

	public function getKeysArrayFiltered(Type $filterValueType, TrinaryLogic $strict): Type
	{
		return new ErrorType();
	}

	public function getKeysArray(): Type
	{
		return $this;
	}

	public function getValuesArray(): Type
	{
		return $this;
	}

	public function chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		return $this;
	}

	public function fillKeysArray(Type $valueType): Type
	{
		return $this;
	}

	public function flipArray(): Type
	{
		return $this;
	}

	public function intersectKeyArray(Type $otherArraysType): Type
	{
		return new MixedType();
	}

	public function popArray(): Type
	{
		return new MixedType();
	}

	public function reverseArray(TrinaryLogic $preserveKeys): Type
	{
		return $this;
	}

	public function searchArray(Type $needleType, ?TrinaryLogic $strict = null): Type
	{
		return new MixedType();
	}

	public function shiftArray(): Type
	{
		return new MixedType();
	}

	public function shuffleArray(): Type
	{
		return $this;
	}

	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		if ((new ConstantIntegerType(0))->isSuperTypeOf($offsetType)->yes() && $lengthType->isNull()->yes()) {
			return $this;
		}

		return new MixedType();
	}

	public function spliceArray(Type $offsetType, Type $lengthType, Type $replacementType): Type
	{
		if (
			(new ConstantIntegerType(0))->isSuperTypeOf($lengthType)->yes()
			|| $replacementType->toArray()->isIterableAtLeastOnce()->yes()
		) {
			return $this;
		}

		return new MixedType();
	}

	public function makeListMaybe(): Type
	{
		// Non-emptiness is independent of list-ness; weaken-list keeps it.
		return $this;
	}

	public function mapValueType(callable $cb): Type
	{
		// Mapping doesn't change the entry count; non-emptiness is preserved.
		return $this;
	}

	public function mapKeyType(callable $cb): Type
	{
		return $this;
	}

	public function makeAllArrayKeysOptional(): Type
	{
		// Without `ConstantArrayType` keys to mark optional, this is a no-op.
		// Non-emptiness is unrelated to per-key optionality and is preserved.
		return $this;
	}

	public function changeKeyCaseArray(?int $case): Type
	{
		// Case-folding keys doesn't change the entry count.
		return $this;
	}

	public function filterArrayRemovingFalsey(): Type
	{
		// Filtering may leave the array empty — drop the assertion.
		return new MixedType();
	}

	public function isIterable(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function getArraySize(): Type
	{
		return IntegerRangeType::fromInterval(1, null);
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

	public function isArray(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isConstantArray(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isOversizedArray(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isList(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
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
		return TrinaryLogic::createNo();
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

	public function isLowercaseString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isClassString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isUppercaseString(): TrinaryLogic
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
		if ($type->isArray()->yes() && $type->isIterableAtLeastOnce()->no()) {
			return new ConstantBooleanType(false);
		}

		return new BooleanType();
	}

	public function toNumber(): Type
	{
		return new ErrorType();
	}

	public function toBitwiseNotType(): Type
	{
		return new ErrorType();
	}

	public function toAbsoluteNumber(): Type
	{
		return new ErrorType();
	}

	public function toInteger(): Type
	{
		return new ConstantIntegerType(1);
	}

	public function toFloat(): Type
	{
		return new ConstantFloatType(1.0);
	}

	public function toString(): Type
	{
		return new ErrorType();
	}

	public function toArray(): Type
	{
		return $this;
	}

	public function toArrayKey(): Type
	{
		return new ErrorType();
	}

	public function toCoercedArgumentType(bool $strictTypes): Type
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

	public function exponentiate(Type $exponent): Type
	{
		return new ErrorType();
	}

	public function getFiniteTypes(): array
	{
		return [];
	}

	public function getDefaultBaseType(): Type
	{
		return new ArrayType(new MixedType(), new MixedType());
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode('non-empty-array');
	}

	public function hasTemplateOrLateResolvableType(): bool
	{
		return false;
	}

}
