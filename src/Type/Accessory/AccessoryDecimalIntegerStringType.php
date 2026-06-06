<?php declare(strict_types = 1);

namespace PHPStan\Type\Accessory;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\StringType;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function count;

/**
 * This accessory type is coupled with `Type::isDecimalIntegerString()` method.
 *
 * When inverse=false, this represents strings containing decimal integers.
 * These are guaranteed to be cast to an integer in an array key.
 * Examples of constant values covered by this type: "0", "1", "1234", "-1"
 *
 * When inverse=true, this represents strings containing non-decimal integers and other text.
 * These are guaranteed to stay as string in an array key.
 * Examples of constant values covered by this type: "+1", "00", "18E+3", "1.2", "1,3", "foo"
 *
 * @api
 */
class AccessoryDecimalIntegerStringType implements CompoundType, AccessoryType
{

	use NonArrayTypeTrait;
	use NonObjectTypeTrait;
	use NonIterableTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;
	use NonGenericTypeTrait;

	/** @api */
	public function __construct(private bool $inverse = false)
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
		$isDecimalIntegerString = $type->isDecimalIntegerString();

		if (
			$type->isString()->yes()
			&& ($this->inverse ? $isDecimalIntegerString->no() : $isDecimalIntegerString->yes())
		) {
			return AcceptsResult::createYes();
		}

		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		$result = $type->isString()->and($this->inverse ? $isDecimalIntegerString->negate() : $isDecimalIntegerString);

		return new AcceptsResult($result, []);
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		if ($this->equals($type)) {
			return IsSuperTypeOfResult::createYes();
		}

		$isDecimalIntegerString = $type->isDecimalIntegerString();
		$result = $type->isString()->and($this->inverse ? $isDecimalIntegerString->negate() : $isDecimalIntegerString);

		return new IsSuperTypeOfResult($result, []);
	}

	public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult
	{
		if ($otherType instanceof UnionType || $otherType instanceof IntersectionType) {
			return $otherType->isSuperTypeOf($this);
		}

		if (
			(
				$otherType instanceof AccessoryNumericStringType
				|| $otherType instanceof AccessoryLowercaseStringType
				|| $otherType instanceof AccessoryUppercaseStringType
			)
			&& !$this->inverse
		) {
			return IsSuperTypeOfResult::createYes();
		}

		$otherTypeResult = $otherType->isString()->and($this->inverse ? $otherType->isDecimalIntegerString()->negate() : $otherType->isDecimalIntegerString());

		return new IsSuperTypeOfResult(
			$otherTypeResult->and($otherType->equals($this) ? TrinaryLogic::createYes() : TrinaryLogic::createMaybe()),
			[],
		);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		return $this->isSubTypeOf($acceptingType)->toAcceptsResult();
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self && $this->inverse === $type->inverse;
	}

	public function describe(VerbosityLevel $level): string
	{
		return $this->inverse ? 'non-decimal-int-string' : 'decimal-int-string';
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

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($this->inverse) {
			return null;
		}

		// `"0"` is the only falsy decimal integer string, so removing it
		// from a (non-inverse) decimal-int-string makes it non-falsy.
		$constantStrings = $typeToRemove->getConstantStrings();
		if (count($constantStrings) === 1 && $constantStrings[0]->getValue() === '0') {
			return new IntersectionType([new StringType(), $this, new AccessoryNonFalsyStringType()]);
		}

		return null;
	}

	public function toNumber(): Type
	{
		if ($this->inverse) {
			return new UnionType([
				$this->toInteger(),
				$this->toFloat(),
			]);
		}

		return $this->toInteger();
	}

	public function toAbsoluteNumber(): Type
	{
		return $this->toNumber()->toAbsoluteNumber();
	}

	public function toBitwiseNotType(): Type
	{
		// Decimal integer strings are non-empty when not inverted
		// (`"0"` / `"123"` are still at least one character). `~$s`
		// returns a string of the same length, so the non-empty flag
		// survives. The decimal-integer property doesn't survive the
		// bitwise-not, hence we drop the accessory.
		return $this->isNonEmptyString()->yes()
			? new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()])
			: new StringType();
	}

	public function toBoolean(): BooleanType
	{
		return $this->isNonFalsyString()->negate()->toBooleanType();
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
			isList: TrinaryLogic::createYes(),
		);
	}

	public function toArrayKey(): Type
	{
		if ($this->inverse) {
			return $this;
		}

		return new IntegerType();
	}

	public function toCoercedArgumentType(bool $strictTypes): Type
	{
		if (!$strictTypes) {
			return TypeCombinator::union($this->toInteger(), $this->toFloat(), $this, $this->toBoolean());
		}

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

	public function isCallable(): TrinaryLogic
	{
		return $this->inverse ? TrinaryLogic::createMaybe() : TrinaryLogic::createNo();
	}

	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		if ($this->inverse) {
			return [new TrivialParametersAcceptor()];
		}

		throw new ShouldNotHappenException();
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
		return $this->inverse ? TrinaryLogic::createMaybe() : TrinaryLogic::createYes();
	}

	public function isDecimalIntegerString(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean(!$this->inverse);
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return $this->inverse ? TrinaryLogic::createMaybe() : TrinaryLogic::createYes();
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
		return $this->inverse ? TrinaryLogic::createMaybe() : TrinaryLogic::createYes();
	}

	public function isUppercaseString(): TrinaryLogic
	{
		return $this->inverse ? TrinaryLogic::createMaybe() : TrinaryLogic::createYes();
	}

	public function isClassString(): TrinaryLogic
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
		return TrinaryLogic::createYes();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		if ($type->isNull()->yes()) {
			return new ConstantBooleanType(false);
		}

		if ($type->isString()->yes()) {
			if ($this->inverse) {
				if ($type->isDecimalIntegerString()->yes()) {
					return new ConstantBooleanType(false);
				}
			} elseif ($type->isDecimalIntegerString()->no()) {
				return new ConstantBooleanType(false);
			}
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

	public function getDefaultBaseType(): Type
	{
		return new StringType();
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode($this->inverse ? 'non-decimal-int-string' : 'decimal-int-string');
	}

	public function hasTemplateOrLateResolvableType(): bool
	{
		return false;
	}

}
