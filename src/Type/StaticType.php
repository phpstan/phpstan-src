<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;

/** @api */
class StaticType implements TypeWithClassName
{

	use NonGenericTypeTrait;
	use UndecidedComparisonTypeTrait;

	private ClassReflection $classReflection;

	private ?\PHPStan\Type\ObjectType $staticObjectType = null;

	private string $baseClass;

	/**
	 * @api
	 */
	public function __construct(ClassReflection $classReflection)
	{
		$this->classReflection = $classReflection;
		$this->baseClass = $classReflection->getName();
	}

	public function getClassName(): string
	{
		return $this->baseClass;
	}

	public function getClassReflection(): ?ClassReflection
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
				$typeMap = $this->classReflection->getActiveTemplateTypeMap()->map(static function (string $name, Type $type): Type {
					return TemplateTypeHelper::toArgument($type);
				});
				return $this->staticObjectType = new GenericObjectType(
					$this->classReflection->getName(),
					$this->classReflection->typeMapToList($typeMap)
				);
			}

			return $this->staticObjectType = new ObjectType($this->classReflection->getName(), null, $this->classReflection);
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

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		if (!$type instanceof static) {
			return TrinaryLogic::createNo();
		}

		return $this->getStaticObjectType()->accepts($type->getStaticObjectType(), $strictTypes);
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
			$classReflection = $type->getClassReflection();
			if ($result->yes() && $classReflection !== null && $classReflection->isFinal()) {
				return $result;
			}

			return TrinaryLogic::createMaybe()->and($result);
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

		/** @var StaticType $type */
		$type = $type;
		return $this->getStaticObjectType()->equals($type->getStaticObjectType());
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('static(%s)', $this->getClassName());
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
			function (Type $type) use ($scope): Type {
				return $this->transformStaticType($type, $scope);
			}
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

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): MethodReflection
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
			function (Type $type) use ($scope): Type {
				return $this->transformStaticType($type, $scope);
			}
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
		return new self($classReflection);
	}

	public function isIterable(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isIterable();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isIterableAtLeastOnce();
	}

	public function getIterableKeyType(): Type
	{
		return $this->getStaticObjectType()->getIterableKeyType();
	}

	public function getIterableValueType(): Type
	{
		return $this->getStaticObjectType()->getIterableValueType();
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

	public function isCallable(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isCallable();
	}

	public function isArray(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isArray();
	}

	public function isNumericString(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isNumericString();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isNonEmptyString();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return $this->getStaticObjectType()->isLiteralString();
	}

	/**
	 * @param \PHPStan\Reflection\ClassMemberAccessAnswerer $scope
	 * @return \PHPStan\Reflection\ParametersAcceptor[]
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

	public function toBoolean(): BooleanType
	{
		return $this->getStaticObjectType()->toBoolean();
	}

	public function traverse(callable $cb): Type
	{
		return $this;
	}

	/**
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		if ($reflectionProvider->hasClass($properties['baseClass'])) {
			return new self($reflectionProvider->getClass($properties['baseClass']));
		}

		return new ErrorType();
	}

}
