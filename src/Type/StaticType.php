<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\TruthyBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;

class StaticType implements TypeWithClassName
{

	use TruthyBooleanTypeTrait;
	use NonGenericTypeTrait;
	use UndecidedComparisonTypeTrait;

	private string $baseClass;

	private ?\PHPStan\Type\ObjectType $staticObjectType = null;

	public function __construct(string $baseClass)
	{
		$this->baseClass = $baseClass;
	}

	public function getClassName(): string
	{
		return $this->baseClass;
	}

	public function getAncestorWithClassName(string $className): ?ObjectType
	{
		return $this->getStaticObjectType()->getAncestorWithClassName($className);
	}

	public function getStaticObjectType(): ObjectType
	{
		if ($this->staticObjectType === null) {
			$this->staticObjectType = new ObjectType($this->baseClass);
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

	public function getBaseClass(): string
	{
		return $this->baseClass;
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return CompoundTypeHelper::accepts($type, $this, $strictTypes);
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
			return TrinaryLogic::createMaybe()->and($this->getStaticObjectType()->isSuperTypeOf($type));
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
		return $this->getStaticObjectType()->getProperty($propertyName, $scope);
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
		return $this->getStaticObjectType()->getMethod($methodName, $scope);
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
		return new self($classReflection->getName());
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

	public function setOffsetValueType(?Type $offsetType, Type $valueType): Type
	{
		return $this->getStaticObjectType()->setOffsetValueType($offsetType, $valueType);
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
		return new self($properties['baseClass']);
	}

}
