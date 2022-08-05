<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\Dummy\DummyConstantReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\Dummy\DummyPropertyReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use function sprintf;

/** @api */
class MixedType implements CompoundType, SubtractableType
{

	use NonGenericTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;
	use NonGeneralizableTypeTrait;

	private ?Type $subtractedType;

	/** @api */
	public function __construct(
		private bool $isExplicitMixed = false,
		?Type $subtractedType = null,
	)
	{
		if ($subtractedType instanceof NeverType) {
			$subtractedType = null;
		}

		$this->subtractedType = $subtractedType;
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return [];
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isSuperTypeOfMixed(MixedType $type): TrinaryLogic
	{
		if ($this->subtractedType === null) {
			if ($this->isExplicitMixed) {
				if ($type->isExplicitMixed) {
					return TrinaryLogic::createYes();
				}
				return TrinaryLogic::createMaybe();
			}

			return TrinaryLogic::createYes();
		}

		if ($type->subtractedType === null) {
			return TrinaryLogic::createMaybe();
		}

		$isSuperType = $type->subtractedType->isSuperTypeOf($this->subtractedType);
		if ($isSuperType->yes()) {
			if ($this->isExplicitMixed) {
				if ($type->isExplicitMixed) {
					return TrinaryLogic::createYes();
				}
				return TrinaryLogic::createMaybe();
			}

			return TrinaryLogic::createYes();
		}

		return TrinaryLogic::createMaybe();
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($this->subtractedType === null || $type instanceof NeverType) {
			return TrinaryLogic::createYes();
		}

		if ($type instanceof self) {
			if ($type->subtractedType === null) {
				return TrinaryLogic::createMaybe();
			}
			$isSuperType = $type->subtractedType->isSuperTypeOf($this->subtractedType);
			if ($isSuperType->yes()) {
				return TrinaryLogic::createYes();
			}

			return TrinaryLogic::createMaybe();
		}

		return $this->subtractedType->isSuperTypeOf($type)->negate();
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		return new self($this->isExplicitMixed);
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return $this;
	}

	public function isCallable(): TrinaryLogic
	{
		if (
			$this->subtractedType !== null
			&& $this->subtractedType->isCallable()->yes()
		) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createMaybe();
	}

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		return [new TrivialParametersAcceptor()];
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		if ($this->subtractedType === null) {
			if ($type->subtractedType === null) {
				return true;
			}

			return false;
		}

		if ($type->subtractedType === null) {
			return false;
		}

		return $this->subtractedType->equals($type->subtractedType);
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof self && !$otherType instanceof TemplateMixedType) {
			return TrinaryLogic::createYes();
		}

		if ($this->subtractedType !== null) {
			$isSuperType = $this->subtractedType->isSuperTypeOf($otherType);
			if ($isSuperType->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		$isSuperType = $this->isSuperTypeOf($acceptingType);
		if ($isSuperType->no()) {
			return $isSuperType;
		}
		return TrinaryLogic::createYes();
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		$property = new DummyPropertyReflection();
		return new CallbackUnresolvedPropertyPrototypeReflection(
			$property,
			$property->getDeclaringClass(),
			false,
			static fn (Type $type): Type => $type,
		);
	}

	public function canCallMethods(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): MethodReflection
	{
		return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
	}

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
	{
		$method = new DummyMethodReflection($methodName);
		return new CallbackUnresolvedMethodPrototypeReflection(
			$method,
			$method->getDeclaringClass(),
			false,
			static fn (Type $type): Type => $type,
		);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		return new DummyConstantReflection($constantName);
	}

	public function isCloneable(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function describe(VerbosityLevel $level): string
	{
		return $level->handle(
			static fn (): string => 'mixed',
			static fn (): string => 'mixed',
			function () use ($level): string {
				$description = 'mixed';
				if ($this->subtractedType !== null) {
					$description .= sprintf('~%s', $this->subtractedType->describe($level));
				}

				return $description;
			},
			function () use ($level): string {
				$description = 'mixed';
				if ($this->subtractedType !== null) {
					$description .= sprintf('~%s', $this->subtractedType->describe($level));
				}

				if ($this->isExplicitMixed) {
					$description .= '=explicit';
				} else {
					$description .= '=implicit';
				}

				return $description;
			},
		);
	}

	public function toBoolean(): BooleanType
	{
		if ($this->subtractedType !== null && StaticTypeFactory::falsey()->equals($this->subtractedType)) {
			return new ConstantBooleanType(true);
		}

		return new BooleanType();
	}

	public function toNumber(): Type
	{
		return new UnionType([
			$this->toInteger(),
			$this->toFloat(),
		]);
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
		return new StringType();
	}

	public function toArray(): Type
	{
		$mixed = new self($this->isExplicitMixed);

		return new ArrayType($mixed, $mixed);
	}

	public function isIterable(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getIterableKeyType(): Type
	{
		return new self($this->isExplicitMixed);
	}

	public function getIterableValueType(): Type
	{
		return new self($this->isExplicitMixed);
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		return new self($this->isExplicitMixed);
	}

	public function isExplicitMixed(): bool
	{
		return $this->isExplicitMixed;
	}

	public function subtract(Type $type): Type
	{
		if ($type instanceof self && !$type instanceof TemplateType) {
			return new NeverType();
		}
		if ($this->subtractedType !== null) {
			$type = TypeCombinator::union($this->subtractedType, $type);
		}

		return new self($this->isExplicitMixed, $type);
	}

	public function getTypeWithoutSubtractedType(): Type
	{
		return new self($this->isExplicitMixed);
	}

	public function changeSubtractedType(?Type $subtractedType): Type
	{
		return new self($this->isExplicitMixed, $subtractedType);
	}

	public function getSubtractedType(): ?Type
	{
		return $this->subtractedType;
	}

	public function traverse(callable $cb): Type
	{
		return $this;
	}

	public function isArray(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
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

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($this->isSuperTypeOf($typeToRemove)->yes()) {
			return $this->subtract($typeToRemove);
		}

		return null;
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['isExplicitMixed'],
			$properties['subtractedType'] ?? null,
		);
	}

}
