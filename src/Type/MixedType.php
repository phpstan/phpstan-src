<?php declare(strict_types = 1);

namespace PHPStan\Type;

use ArrayAccess;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\Dummy\DummyConstantReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\Dummy\DummyPropertyReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
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

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->acceptsWithReason($type, $strictTypes)->result;
	}

	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
	{
		return AcceptsResult::createYes();
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
		return $this->isSuperTypeOfWithReason($type)->result;
	}

	public function isSuperTypeOfWithReason(Type $type): IsSuperTypeOfResult
	{
		if ($this->subtractedType === null || $type instanceof NeverType) {
			return IsSuperTypeOfResult::createYes();
		}

		if ($type instanceof self) {
			if ($type->subtractedType === null) {
				return IsSuperTypeOfResult::createMaybe();
			}
			$isSuperType = $type->subtractedType->isSuperTypeOfWithReason($this->subtractedType);
			if ($isSuperType->yes()) {
				return $isSuperType;
			}

			return IsSuperTypeOfResult::createMaybe();
		}

		$result = $this->subtractedType->isSuperTypeOfWithReason($type)->negate();
		if ($result->no()) {
			return IsSuperTypeOfResult::createNo([
				sprintf(
					'Type %s has already been eliminated from %s.',
					$this->subtractedType->describe(VerbosityLevel::precise()),
					$this->describe(VerbosityLevel::typeOnly()),
				),
			]);
		}

		return $result;
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		return new self($this->isExplicitMixed);
	}

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		return new self($this->isExplicitMixed);
	}

	public function unsetOffset(Type $offsetType): Type
	{
		if ($this->subtractedType !== null) {
			return new self($this->isExplicitMixed, TypeCombinator::remove($this->subtractedType, new ConstantArrayType([], [])));
		}
		return $this;
	}

	public function getKeysArray(): Type
	{
		if ($this->isArray()->no()) {
			return new ErrorType();
		}

		return AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), new UnionType([new IntegerType(), new StringType()])));
	}

	public function getValuesArray(): Type
	{
		if ($this->isArray()->no()) {
			return new ErrorType();
		}

		return AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), new MixedType($this->isExplicitMixed)));
	}

	public function chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		if ($this->isArray()->no()) {
			return new ErrorType();
		}

		return AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), new MixedType($this->isExplicitMixed)));
	}

	public function fillKeysArray(Type $valueType): Type
	{
		if ($this->isArray()->no()) {
			return new ErrorType();
		}

		return new ArrayType($this->getIterableValueType(), $valueType);
	}

	public function flipArray(): Type
	{
		if ($this->isArray()->no()) {
			return new ErrorType();
		}

		return new ArrayType(new MixedType($this->isExplicitMixed), new MixedType($this->isExplicitMixed));
	}

	public function intersectKeyArray(Type $otherArraysType): Type
	{
		if ($this->isArray()->no()) {
			return new ErrorType();
		}

		return new ArrayType(new MixedType($this->isExplicitMixed), new MixedType($this->isExplicitMixed));
	}

	public function popArray(): Type
	{
		if ($this->isArray()->no()) {
			return new ErrorType();
		}

		return new ArrayType(new MixedType($this->isExplicitMixed), new MixedType($this->isExplicitMixed));
	}

	public function reverseArray(TrinaryLogic $preserveKeys): Type
	{
		if ($this->isArray()->no()) {
			return new ErrorType();
		}

		return new ArrayType(new MixedType($this->isExplicitMixed), new MixedType($this->isExplicitMixed));
	}

	public function searchArray(Type $needleType): Type
	{
		if ($this->isArray()->no()) {
			return new ErrorType();
		}

		return TypeCombinator::union(new IntegerType(), new StringType(), new ConstantBooleanType(false));
	}

	public function shiftArray(): Type
	{
		if ($this->isArray()->no()) {
			return new ErrorType();
		}

		return new ArrayType(new MixedType($this->isExplicitMixed), new MixedType($this->isExplicitMixed));
	}

	public function shuffleArray(): Type
	{
		if ($this->isArray()->no()) {
			return new ErrorType();
		}

		return AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), new MixedType($this->isExplicitMixed)));
	}

	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		if ($this->isArray()->no()) {
			return new ErrorType();
		}

		return new ArrayType(new MixedType($this->isExplicitMixed), new MixedType($this->isExplicitMixed));
	}

	public function isCallable(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new CallableType())->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function getEnumCases(): array
	{
		return [];
	}

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
		return $this->isSubTypeOfWithReason($otherType)->result;
	}

	public function isSubTypeOfWithReason(Type $otherType): IsSuperTypeOfResult
	{
		if ($otherType instanceof self && !$otherType instanceof TemplateMixedType) {
			return IsSuperTypeOfResult::createYes();
		}

		if ($this->subtractedType !== null) {
			$isSuperType = $this->subtractedType->isSuperTypeOfWithReason($otherType);
			if ($isSuperType->yes()) {
				return IsSuperTypeOfResult::createNo();
			}
		}

		return IsSuperTypeOfResult::createMaybe();
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		return $this->isAcceptedWithReasonBy($acceptingType, $strictTypes)->result;
	}

	public function isAcceptedWithReasonBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		$isSuperType = $this->isSuperTypeOfWithReason($acceptingType)->toAcceptsResult();
		if ($isSuperType->no()) {
			return $isSuperType;
		}
		return AcceptsResult::createYes();
	}

	public function getTemplateType(string $ancestorClassName, string $templateTypeName): Type
	{
		return new self();
	}

	public function isObject(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new ObjectWithoutClassType())->yes()) {
				return TrinaryLogic::createNo();
			}
		}
		return TrinaryLogic::createMaybe();
	}

	public function isEnum(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new ObjectWithoutClassType())->yes()) {
				return TrinaryLogic::createNo();
			}
		}
		return TrinaryLogic::createMaybe();
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

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
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
					$description .= $this->subtractedType instanceof UnionType
						? sprintf('~(%s)', $this->subtractedType->describe($level))
						: sprintf('~%s', $this->subtractedType->describe($level));
				}

				return $description;
			},
			function () use ($level): string {
				$description = 'mixed';
				if ($this->subtractedType !== null) {
					$description .= $this->subtractedType instanceof UnionType
						? sprintf('~(%s)', $this->subtractedType->describe($level))
						: sprintf('~%s', $this->subtractedType->describe($level));
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
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(StaticTypeFactory::falsey())->yes()) {
				return new ConstantBooleanType(true);
			}
		}

		return new BooleanType();
	}

	public function toNumber(): Type
	{
		return TypeCombinator::union(
			$this->toInteger(),
			$this->toFloat(),
		);
	}

	public function toAbsoluteNumber(): Type
	{
		return $this->toNumber()->toAbsoluteNumber();
	}

	public function toInteger(): Type
	{
		$castsToZero = new UnionType([
			new NullType(),
			new ConstantBooleanType(false),
			new ConstantIntegerType(0),
			new ConstantArrayType([], []),
			new StringType(),
			new FloatType(), // every 0.x float casts to int(0)
		]);
		if (
			$this->subtractedType !== null
			&& $this->subtractedType->isSuperTypeOf($castsToZero)->yes()
		) {
			return new UnionType([
				IntegerRangeType::fromInterval(null, -1),
				IntegerRangeType::fromInterval(1, null),
			]);
		}

		return new IntegerType();
	}

	public function toFloat(): Type
	{
		return new FloatType();
	}

	public function toString(): Type
	{
		if ($this->subtractedType !== null) {
			$castsToEmptyString = new UnionType([
				new NullType(),
				new ConstantBooleanType(false),
				new ConstantStringType(''),
			]);
			if ($this->subtractedType->isSuperTypeOf($castsToEmptyString)->yes()) {
				$accessories = [
					new StringType(),
					new AccessoryNonEmptyStringType(),
				];

				$castsToZeroString = new UnionType([
					new ConstantFloatType(0.0),
					new ConstantStringType('0'),
					new ConstantIntegerType(0),
				]);
				if ($this->subtractedType->isSuperTypeOf($castsToZeroString)->yes()) {
					$accessories[] = new AccessoryNonFalsyStringType();
				}
				return new IntersectionType(
					$accessories,
				);
			}
		}

		return new StringType();
	}

	public function toArray(): Type
	{
		$mixed = new self($this->isExplicitMixed);

		return new ArrayType($mixed, $mixed);
	}

	public function toArrayKey(): Type
	{
		return new BenevolentUnionType([new IntegerType(), new StringType()]);
	}

	public function isIterable(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new IterableType(new MixedType(), new MixedType()))->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return $this->isIterable();
	}

	public function getArraySize(): Type
	{
		if ($this->isIterable()->no()) {
			return new ErrorType();
		}

		return IntegerRangeType::fromInterval(0, null);
	}

	public function getIterableKeyType(): Type
	{
		return new self($this->isExplicitMixed);
	}

	public function getFirstIterableKeyType(): Type
	{
		return new self($this->isExplicitMixed);
	}

	public function getLastIterableKeyType(): Type
	{
		return new self($this->isExplicitMixed);
	}

	public function getIterableValueType(): Type
	{
		return new self($this->isExplicitMixed);
	}

	public function getFirstIterableValueType(): Type
	{
		return new self($this->isExplicitMixed);
	}

	public function getLastIterableValueType(): Type
	{
		return new self($this->isExplicitMixed);
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			$offsetAccessibles = new UnionType([
				new StringType(),
				new ArrayType(new MixedType(), new MixedType()),
				new ObjectType(ArrayAccess::class),
			]);

			if ($this->subtractedType->isSuperTypeOf($offsetAccessibles)->yes()) {
				return TrinaryLogic::createNo();
			}
		}
		return TrinaryLogic::createMaybe();
	}

	public function isOffsetAccessLegal(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new ObjectWithoutClassType())->yes()) {
				return TrinaryLogic::createYes();
			}
		}
		return TrinaryLogic::createMaybe();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		if ($this->isOffsetAccessible()->no()) {
			return TrinaryLogic::createNo();
		}

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

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		return $this;
	}

	public function isArray(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new ArrayType(new MixedType(), new MixedType()))->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isConstantArray(): TrinaryLogic
	{
		return $this->isArray();
	}

	public function isOversizedArray(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			$oversizedArray = TypeCombinator::intersect(
				new ArrayType(new MixedType(), new MixedType()),
				new OversizedArrayType(),
			);

			if ($this->subtractedType->isSuperTypeOf($oversizedArray)->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isList(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			$list = TypeCombinator::intersect(
				new ArrayType(new IntegerType(), new MixedType()),
				new AccessoryArrayListType(),
			);

			if ($this->subtractedType->isSuperTypeOf($list)->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isNull(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new NullType())->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
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
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new ConstantBooleanType(true))->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isFalse(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new ConstantBooleanType(false))->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isBoolean(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new BooleanType())->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isFloat(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new FloatType())->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isInteger(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new IntegerType())->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isString(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new StringType())->yes()) {
				return TrinaryLogic::createNo();
			}
		}
		return TrinaryLogic::createMaybe();
	}

	public function isNumericString(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			$numericString = TypeCombinator::intersect(
				new StringType(),
				new AccessoryNumericStringType(),
			);

			if ($this->subtractedType->isSuperTypeOf($numericString)->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			$nonEmptyString = TypeCombinator::intersect(
				new StringType(),
				new AccessoryNonEmptyStringType(),
			);

			if ($this->subtractedType->isSuperTypeOf($nonEmptyString)->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			$nonFalsyString = TypeCombinator::intersect(
				new StringType(),
				new AccessoryNonFalsyStringType(),
			);

			if ($this->subtractedType->isSuperTypeOf($nonFalsyString)->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isLiteralString(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			$literalString = TypeCombinator::intersect(
				new StringType(),
				new AccessoryLiteralStringType(),
			);

			if ($this->subtractedType->isSuperTypeOf($literalString)->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isLowercaseString(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			$lowercaseString = TypeCombinator::intersect(
				new StringType(),
				new AccessoryLowercaseStringType(),
			);

			if ($this->subtractedType->isSuperTypeOf($lowercaseString)->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isUppercaseString(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			$uppercaseString = TypeCombinator::intersect(
				new StringType(),
				new AccessoryUppercaseStringType(),
			);

			if ($this->subtractedType->isSuperTypeOf($uppercaseString)->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isClassStringType(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new StringType())->yes()) {
				return TrinaryLogic::createNo();
			}
			if ($this->subtractedType->isSuperTypeOf(new ClassStringType())->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function getClassStringObjectType(): Type
	{
		if (!$this->isClassStringType()->no()) {
			return new ObjectWithoutClassType();
		}

		return new ErrorType();
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		$objectOrClass = new UnionType([
			new ObjectWithoutClassType(),
			new ClassStringType(),
		]);
		if (!$this->isSuperTypeOf($objectOrClass)->no()) {
			return new ObjectWithoutClassType();
		}

		return new ErrorType();
	}

	public function isVoid(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new VoidType())->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isScalar(): TrinaryLogic
	{
		if ($this->subtractedType !== null) {
			if ($this->subtractedType->isSuperTypeOf(new UnionType([new BooleanType(), new FloatType(), new IntegerType(), new StringType()]))->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		return new BooleanType();
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($this->isSuperTypeOf($typeToRemove)->yes()) {
			return $this->subtract($typeToRemove);
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
		return new IdentifierTypeNode('mixed');
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
