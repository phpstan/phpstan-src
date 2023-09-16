<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use function get_class;
use function sprintf;

/** @api */
class StaticType implements TypeWithClassName, SubtractableType
{

	use NonGenericTypeTrait;
	use UndecidedComparisonTypeTrait;
	use NonGeneralizableTypeTrait;

	private ?Type $subtractedType;

	private ?ObjectType $staticObjectType = null;

	private string $baseClass;

	/**
	 * @api
	 */
	public function __construct(
		private ClassReflection $classReflection,
		?Type $subtractedType = null,
	)
	{
		if ($subtractedType instanceof NeverType) {
			$subtractedType = null;
		}

		$this->subtractedType = $subtractedType;
		$this->baseClass = $classReflection->getName();
	}

	public function getClassName(): string
	{
		return $this->baseClass;
	}

	public function getClassReflection(): ClassReflection
	{
		return $this->classReflection;
	}

	public function getAncestorWithClassName(string $className): ?TypeWithClassName
	{
		$ancestor = $this->getStaticObjectType()->getAncestorWithClassName($className);
		if ($ancestor === null) {
			return null;
		}

		$classReflection = $ancestor->getClassReflection();
		if ($classReflection !== null) {
			return $this->changeBaseClass($classReflection);
		}

		return null;
	}

	public function getStaticObjectType(): ObjectType
	{
		if ($this->staticObjectType === null) {
			if ($this->classReflection->isGeneric()) {
				$typeMap = $this->classReflection->getActiveTemplateTypeMap()->map(static fn (string $name, Type $type): Type => TemplateTypeHelper::toArgument($type));
				$varianceMap = $this->classReflection->getCallSiteVarianceMap();
				return $this->staticObjectType = new GenericObjectType(
					$this->classReflection->getName(),
					$this->classReflection->typeMapToList($typeMap),
					$this->subtractedType,
					null,
					$this->classReflection->varianceMapToList($varianceMap),
				);
			}

			return $this->staticObjectType = new ObjectType($this->classReflection->getName(), $this->subtractedType, $this->classReflection);
		}

		return $this->staticObjectType;
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return $this->getStaticObjectType()->getReferencedClasses();
	}

	public function getObjectClassNames(): array
	{
		return $this->getStaticObjectType()->getObjectClassNames();
	}

	public function getObjectClassReflections(): array
	{
		return $this->getStaticObjectType()->getObjectClassReflections();
	}

	public function getArrays(): array
	{
		return $this->getStaticObjectType()->getArrays();
	}

	public function getConstantArrays(): array
	{
		return $this->getStaticObjectType()->getConstantArrays();
	}

	public function getConstantStrings(): array
	{
		return $this->getStaticObjectType()->getConstantStrings();
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

		if (!$type instanceof static) {
			return AcceptsResult::createNo();
		}

		return $this->getStaticObjectType()->acceptsWithReason($type->getStaticObjectType(), $strictTypes);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			return $this->getStaticObjectType()->isSuperTypeOf($type);
		}

		if ($type instanceof ObjectWithoutClassType) {
			return TrinaryLogic::createMaybe();
		}

		if ($type instanceof ObjectType) {
			$result = $this->getStaticObjectType()->isSuperTypeOf($type);
			if ($result->yes()) {
				$classReflection = $type->getClassReflection();
				if ($classReflection !== null && $classReflection->isFinal()) {
					return $result;
				}
			}

			return $result->and(TrinaryLogic::createMaybe());
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return TrinaryLogic::createNo();
	}

	public function equals(Type $type): bool
	{
		if (get_class($type) !== static::class) {
			return false;
		}

		return $this->getStaticObjectType()->equals($type->getStaticObjectType());
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('static(%s)', $this->getStaticObjectType()->describe($level));
	}

	public function getTemplateType(string $ancestorClassName, string $templateTypeName): Type
	{
		return $this->getStaticObjectType()->getTemplateType($ancestorClassName, $templateTypeName);
	}

	public function isObject(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isObject();
	}

	public function isEnum(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isEnum();
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return $this->getStaticObjectType()->canAccessProperties();
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return $this->getStaticObjectType()->hasProperty($propertyName);
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		$staticObject = $this->getStaticObjectType();
		$nakedProperty = $staticObject->getUnresolvedPropertyPrototype($propertyName, $scope)->getNakedProperty();

		$ancestor = $this->getAncestorWithClassName($nakedProperty->getDeclaringClass()->getName());
		$classReflection = null;
		if ($ancestor !== null) {
			$classReflection = $ancestor->getClassReflection();
		}
		if ($classReflection === null) {
			$classReflection = $nakedProperty->getDeclaringClass();
		}

		return new CallbackUnresolvedPropertyPrototypeReflection(
			$nakedProperty,
			$classReflection,
			false,
			fn (Type $type): Type => $this->transformStaticType($type, $scope),
		);
	}

	public function canCallMethods(): TrinaryLogic
	{
		return $this->getStaticObjectType()->canCallMethods();
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		return $this->getStaticObjectType()->hasMethod($methodName);
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
	{
		return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
	}

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
	{
		$staticObject = $this->getStaticObjectType();
		$nakedMethod = $staticObject->getUnresolvedMethodPrototype($methodName, $scope)->getNakedMethod();

		$ancestor = $this->getAncestorWithClassName($nakedMethod->getDeclaringClass()->getName());
		$classReflection = null;
		if ($ancestor !== null) {
			$classReflection = $ancestor->getClassReflection();
		}
		if ($classReflection === null) {
			$classReflection = $nakedMethod->getDeclaringClass();
		}

		return new CallbackUnresolvedMethodPrototypeReflection(
			$nakedMethod,
			$classReflection,
			false,
			fn (Type $type): Type => $this->transformStaticType($type, $scope),
		);
	}

	private function transformStaticType(Type $type, ClassMemberAccessAnswerer $scope): Type
	{
		return TypeTraverser::map($type, function (Type $type, callable $traverse) use ($scope): Type {
			if ($type instanceof StaticType) {
				$classReflection = $this->classReflection;
				$isFinal = false;
				if ($scope->isInClass()) {
					$classReflection = $scope->getClassReflection();
					$isFinal = $classReflection->isFinal();
				}
				$type = $type->changeBaseClass($classReflection);
				if (!$isFinal) {
					return $type;
				}

				return $type->getStaticObjectType();
			}

			return $traverse($type);
		});
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return $this->getStaticObjectType()->canAccessConstants();
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return $this->getStaticObjectType()->hasConstant($constantName);
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		return $this->getStaticObjectType()->getConstant($constantName);
	}

	public function changeBaseClass(ClassReflection $classReflection): self
	{
		return new self($classReflection, $this->subtractedType);
	}

	public function isIterable(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isIterable();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isIterableAtLeastOnce();
	}

	public function getArraySize(): Type
	{
		return $this->getStaticObjectType()->getArraySize();
	}

	public function getIterableKeyType(): Type
	{
		return $this->getStaticObjectType()->getIterableKeyType();
	}

	public function getFirstIterableKeyType(): Type
	{
		return $this->getStaticObjectType()->getFirstIterableKeyType();
	}

	public function getLastIterableKeyType(): Type
	{
		return $this->getStaticObjectType()->getLastIterableKeyType();
	}

	public function getIterableValueType(): Type
	{
		return $this->getStaticObjectType()->getIterableValueType();
	}

	public function getFirstIterableValueType(): Type
	{
		return $this->getStaticObjectType()->getFirstIterableValueType();
	}

	public function getLastIterableValueType(): Type
	{
		return $this->getStaticObjectType()->getLastIterableValueType();
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isOffsetAccessible();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return $this->getStaticObjectType()->hasOffsetValueType($offsetType);
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		return $this->getStaticObjectType()->getOffsetValueType($offsetType);
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		return $this->getStaticObjectType()->setOffsetValueType($offsetType, $valueType, $unionValues);
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return $this->getStaticObjectType()->unsetOffset($offsetType);
	}

	public function getKeysArray(): Type
	{
		return $this->getStaticObjectType()->getKeysArray();
	}

	public function getValuesArray(): Type
	{
		return $this->getStaticObjectType()->getValuesArray();
	}

	public function fillKeysArray(Type $valueType): Type
	{
		return $this->getStaticObjectType()->fillKeysArray($valueType);
	}

	public function flipArray(): Type
	{
		return $this->getStaticObjectType()->flipArray();
	}

	public function intersectKeyArray(Type $otherArraysType): Type
	{
		return $this->getStaticObjectType()->intersectKeyArray($otherArraysType);
	}

	public function popArray(): Type
	{
		return $this->getStaticObjectType()->popArray();
	}

	public function searchArray(Type $needleType): Type
	{
		return $this->getStaticObjectType()->searchArray($needleType);
	}

	public function shiftArray(): Type
	{
		return $this->getStaticObjectType()->shiftArray();
	}

	public function shuffleArray(): Type
	{
		return $this->getStaticObjectType()->shuffleArray();
	}

	public function isCallable(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isCallable();
	}

	public function getEnumCases(): array
	{
		return $this->getStaticObjectType()->getEnumCases();
	}

	public function isArray(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isArray();
	}

	public function isConstantArray(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isConstantArray();
	}

	public function isOversizedArray(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isOversizedArray();
	}

	public function isList(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isList();
	}

	public function isNull(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isNull();
	}

	public function isConstantValue(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isConstantValue();
	}

	public function isConstantScalarValue(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isConstantScalarValue();
	}

	public function getConstantScalarTypes(): array
	{
		return $this->getStaticObjectType()->getConstantScalarTypes();
	}

	public function getConstantScalarValues(): array
	{
		return $this->getStaticObjectType()->getConstantScalarValues();
	}

	public function isTrue(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isTrue();
	}

	public function isFalse(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isFalse();
	}

	public function isBoolean(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isBoolean();
	}

	public function isFloat(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isFloat();
	}

	public function isInteger(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isInteger();
	}

	public function isString(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isString();
	}

	public function isNumericString(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isNumericString();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isNonEmptyString();
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isNonFalsyString();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isLiteralString();
	}

	public function isClassStringType(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isClassStringType();
	}

	public function getClassStringObjectType(): Type
	{
		return $this->getStaticObjectType()->getClassStringObjectType();
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return $this;
	}

	public function isVoid(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isVoid();
	}

	public function isScalar(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isScalar();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		return new BooleanType();
	}

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		return $this->getStaticObjectType()->getCallableParametersAcceptors($scope);
	}

	public function isCloneable(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function toNumber(): Type
	{
		return new ErrorType();
	}

	public function toString(): Type
	{
		return $this->getStaticObjectType()->toString();
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
		return $this->getStaticObjectType()->toArray();
	}

	public function toArrayKey(): Type
	{
		return $this->getStaticObjectType()->toArrayKey();
	}

	public function toBoolean(): BooleanType
	{
		return $this->getStaticObjectType()->toBoolean();
	}

	public function traverse(callable $cb): Type
	{
		$subtractedType = $this->subtractedType !== null ? $cb($this->subtractedType) : null;

		if ($subtractedType !== $this->subtractedType) {
			return new self(
				$this->classReflection,
				$subtractedType,
			);
		}

		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if ($this->subtractedType === null) {
			return $this;
		}

		return new self($this->classReflection);
	}

	public function subtract(Type $type): Type
	{
		if ($this->subtractedType !== null) {
			$type = TypeCombinator::union($this->subtractedType, $type);
		}

		return $this->changeSubtractedType($type);
	}

	public function getTypeWithoutSubtractedType(): Type
	{
		return $this->changeSubtractedType(null);
	}

	public function changeSubtractedType(?Type $subtractedType): Type
	{
		if ($subtractedType !== null) {
			$classReflection = $this->getClassReflection();
			if ($classReflection->getAllowedSubTypes() !== null) {
				$objectType = $this->getStaticObjectType()->changeSubtractedType($subtractedType);
				if ($objectType instanceof NeverType) {
					return $objectType;
				}

				if ($objectType instanceof ObjectType && $objectType->getSubtractedType() !== null) {
					return new self($classReflection, $objectType->getSubtractedType());
				}

				return TypeCombinator::intersect($this, $objectType);
			}
		}

		return new self($this->classReflection, $subtractedType);
	}

	public function getSubtractedType(): ?Type
	{
		return $this->subtractedType;
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($this->getStaticObjectType()->isSuperTypeOf($typeToRemove)->yes()) {
			return $this->subtract($typeToRemove);
		}

		return null;
	}

	public function exponentiate(Type $exponent): Type
	{
		return $this->getStaticObjectType()->exponentiate($exponent);
	}

	public function getFiniteTypes(): array
	{
		return $this->getStaticObjectType()->getFiniteTypes();
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode('static');
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		if ($reflectionProvider->hasClass($properties['baseClass'])) {
			return new self($reflectionProvider->getClass($properties['baseClass']), $properties['subtractedType'] ?? null);
		}

		return new ErrorType();
	}

}
