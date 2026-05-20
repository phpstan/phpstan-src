<?php declare(strict_types = 1);

namespace PHPStan\Type\Accessory;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Traits\MaybeCallableTypeTrait;
use PHPStan\Type\Traits\MaybeIterableTypeTrait;
use PHPStan\Type\Traits\MaybeObjectTypeTrait;
use PHPStan\Type\Traits\MaybeOffsetAccessibleTypeTrait;
use PHPStan\Type\Traits\MaybeStringTypeTrait;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\TruthyBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

class HasPropertyType implements AccessoryType, CompoundType
{

	use MaybeCallableTypeTrait;
	use MaybeIterableTypeTrait;
	use MaybeObjectTypeTrait;
	use MaybeOffsetAccessibleTypeTrait;
	use MaybeStringTypeTrait;
	use NonArrayTypeTrait;
	use TruthyBooleanTypeTrait;
	use NonGenericTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;
	use NonRemoveableTypeTrait;
	use NonGeneralizableTypeTrait;

	/** @api */
	public function __construct(private string $propertyName)
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

	public function getClassStringType(): Type
	{
		return new GenericClassStringType($this);
	}

	public function getPropertyName(): string
	{
		return $this->propertyName;
	}

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		return AcceptsResult::createFromBoolean($this->equals($type));
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return new IsSuperTypeOfResult(
			$type->hasInstanceProperty($this->propertyName)->or($type->hasStaticProperty($this->propertyName)),
			[],
		);
	}

	public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult
	{
		if ($otherType instanceof UnionType || $otherType instanceof IntersectionType) {
			return $otherType->isSuperTypeOf($this);
		}

		if ($otherType instanceof self) {
			$limit = TrinaryLogic::createYes();
		} else {
			$limit = TrinaryLogic::createMaybe();
		}

		return new IsSuperTypeOfResult(
			$limit->and($otherType->hasInstanceProperty($this->propertyName)->or($otherType->hasStaticProperty($this->propertyName))),
			[],
		);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		return $this->isSubTypeOf($acceptingType)->toAcceptsResult();
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $this->propertyName === $type->propertyName;
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('hasProperty(%s)', $this->propertyName);
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		if ($this->propertyName === $propertyName) {
			return TrinaryLogic::createYes();
		}

		return TrinaryLogic::createMaybe();
	}

	public function hasInstanceProperty(string $propertyName): TrinaryLogic
	{
		if ($this->propertyName === $propertyName) {
			return TrinaryLogic::createYes();
		}

		return TrinaryLogic::createMaybe();
	}

	public function hasStaticProperty(string $propertyName): TrinaryLogic
	{
		if ($this->propertyName === $propertyName) {
			return TrinaryLogic::createYes();
		}

		return TrinaryLogic::createMaybe();
	}

	public function isNull(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isConstantValue(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
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

	public function getClassStringObjectType(): Type
	{
		return $this;
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return $this;
	}

	public function isVoid(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
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

	public function toString(): Type
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

	public function toArray(): Type
	{
		return new MixedType();
	}

	public function toArrayKey(): Type
	{
		return new ErrorType();
	}

	public function toCoercedArgumentType(bool $strictTypes): Type
	{
		if (!$strictTypes) {
			return TypeCombinator::union($this, $this->toString());
		}

		return $this;
	}

	public function getEnumCases(): array
	{
		return [];
	}

	public function getEnumCaseObject(): ?EnumCaseObjectType
	{
		return null;
	}

	public function traverse(callable $cb): Type
	{
		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		return $this;
	}

	public function shiftLeft(Type $otherType): Type
	{
		return new ErrorType();
	}

	public function modulo(Type $otherType): Type
	{
		return new ErrorType();
	}

	public function divide(Type $otherType): Type
	{
		return new ErrorType();
	}

	public function multiply(Type $otherType): Type
	{
		return new ErrorType();
	}

	public function minus(Type $otherType): Type
	{
		return new ErrorType();
	}

	public function shiftRight(Type $otherType): Type
	{
		return new ErrorType();
	}

	public function getIntegerRanges(): array
	{
		return [];
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
		return new ObjectWithoutClassType();
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode(''); // no PHPDoc representation
	}

	public function hasTemplateOrLateResolvableType(): bool
	{
		return false;
	}

}
