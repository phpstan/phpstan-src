<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\Dummy\ChangedTypeMethodReflection;
use PHPStan\Reflection\Dummy\ChangedTypePropertyReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ResolvedMethodReflection;
use PHPStan\Reflection\ResolvedPropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;

class StaticType implements TypeWithClassName
{

	use NonGenericTypeTrait;
	use UndecidedComparisonTypeTrait;

	private ?ClassReflection $classReflection;

	private ?\PHPStan\Type\ObjectType $staticObjectType = null;

	private string $baseClass;

	/**
	 * @param string|ClassReflection $classReflection
	 */
	public function __construct($classReflection)
	{
		if (is_string($classReflection)) {
			$broker = Broker::getInstance();
			if ($broker->hasClass($classReflection)) {
				$classReflection = $broker->getClass($classReflection);
				$this->classReflection = $classReflection;
				$this->baseClass = $classReflection->getName();
				return;
			}

			$this->classReflection = null;
			$this->baseClass = $classReflection;
			return;
		}

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

		return $this->changeBaseClass($ancestor->getClassReflection() ?? $ancestor->getClassName());
	}

	public function getStaticObjectType(): ObjectType
	{
		if ($this->staticObjectType === null) {
			if ($this->classReflection !== null && $this->classReflection->isGeneric()) {
				$typeMap = $this->classReflection->getActiveTemplateTypeMap()->map(static function (string $name, Type $type): Type {
					return TemplateTypeHelper::toArgument($type);
				});
				return $this->staticObjectType = new GenericObjectType(
					$this->classReflection->getName(),
					$this->classReflection->typeMapToList($typeMap)
				);
			}

			return $this->staticObjectType = new ObjectType($this->baseClass, null, $this->classReflection);
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
		$staticObject = $this->getStaticObjectType();
		$property = $staticObject->getPropertyWithoutTransformingStatic($propertyName, $scope);
		$readableType = $this->transformStaticType($property->getReadableType(), $scope);
		$writableType = $this->transformStaticType($property->getWritableType(), $scope);

		$ancestor = $this->getAncestorWithClassName($property->getDeclaringClass()->getName());
		$classReflection = null;
		if ($ancestor !== null) {
			$classReflection = $ancestor->getClassReflection();
		}
		if ($classReflection === null) {
			$classReflection = $property->getDeclaringClass();
		}

		return new ResolvedPropertyReflection(
			new ChangedTypePropertyReflection($classReflection, $property, $readableType, $writableType),
			$classReflection->getActiveTemplateTypeMap()
		);
	}

	private function transformStaticType(Type $type, ClassMemberAccessAnswerer $scope): Type
	{
		return TypeTraverser::map($type, function (Type $type, callable $traverse) use ($scope): Type {
			if ($type instanceof StaticType) {
				$classReflection = $this->classReflection;
				if ($classReflection === null) {
					$classReflection = $this->baseClass;
				} elseif ($scope->isInClass()) {
					$classReflection = $scope->getClassReflection();
				}
				return $type->changeBaseClass($classReflection);
			}

			return $traverse($type);
		});
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
		$staticObject = $this->getStaticObjectType();
		$method = $staticObject->getMethodWithoutTransformingStatic($methodName, $scope);
		$variants = array_map(function (ParametersAcceptor $acceptor) use ($scope): ParametersAcceptor {
			return new FunctionVariant(
				$acceptor->getTemplateTypeMap(),
				$acceptor->getResolvedTemplateTypeMap(),
				array_map(function (ParameterReflection $parameter) use ($scope): ParameterReflection {
					return new DummyParameter(
						$parameter->getName(),
						$this->transformStaticType($parameter->getType(), $scope),
						$parameter->isOptional(),
						$parameter->passedByReference(),
						$parameter->isVariadic(),
						$parameter->getDefaultValue()
					);
				}, $acceptor->getParameters()),
				$acceptor->isVariadic(),
				$this->transformStaticType($acceptor->getReturnType(), $scope)
			);
		}, $method->getVariants());

		$ancestor = $this->getAncestorWithClassName($method->getDeclaringClass()->getName());
		$classReflection = null;
		if ($ancestor !== null) {
			$classReflection = $ancestor->getClassReflection();
		}
		if ($classReflection === null) {
			$classReflection = $method->getDeclaringClass();
		}

		return new ResolvedMethodReflection(
			new ChangedTypeMethodReflection($classReflection, $method, $variants),
			$classReflection->getActiveTemplateTypeMap()
		);
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

	/**
	 * @param ClassReflection|string $classReflection
	 * @return self
	 */
	public function changeBaseClass($classReflection): self
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
		return new self($properties['baseClass']);
	}

}
