<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;

class SubtractedType implements Type, CompoundType
{
	private $originalType;
	private $subtractedType;

	private function __construct(Type $originalType, Type $subtractedType)
	{
		$this->originalType = $originalType;
		$this->subtractedType = $subtractedType;
	}

	/**
	 * TODO: move/use logic from TypeCombinator here, to create the type
	 * @see TypeCombinator::remove()
	 * The goal should be to normalize the type if possible (subtract a class from a union type of classes etc)
	 * Only if that is not possible use this type, e.g. when subtracting a constant string from the general string type
	 */
	public static function create(Type $originalType, Type $subtractedType): Type
	{
		if ($subtractedType->isSuperTypeOf($originalType)->yes() || $originalType instanceof NeverType) {
			return new NeverType();
		}

		if ($originalType->accepts($subtractedType, false)->no()) {
			return $originalType;
		}

		return new self($originalType, $subtractedType);
	}

	/**
	 * @inheritDoc
	 */
	public function getReferencedClasses(): array
	{
		return array_merge(
			$this->originalType->getReferencedClasses(),
			$this->subtractedType->getReferencedClasses()
		);
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		$original = $this->originalType->accepts($type, $strictTypes);
		if (!$original->yes()) {
			return $original;
		}

		return TrinaryLogic::createFromBoolean(!$this->subtractedType->equals($type));
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		// TODO: Implement isSuperTypeOf() method.
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		return $this->originalType->equals($type) && !$this->subtractedType->equals($type);
	}

	public function describe(VerbosityLevel $level): string
	{
		return $this->originalType->describe($level) . '~' . $this->subtractedType->describe($level);
	}

	public function canAccessProperties(): TrinaryLogic
	{
		if ($this->subtractedType->equals(new ObjectWithoutClassType())) {
			return TrinaryLogic::createNo();
		}

		return $this->originalType->canAccessProperties();
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		if ($this->subtractedType->equals(new ObjectWithoutClassType())) {
			return TrinaryLogic::createNo();
		}

		if ($this->originalType instanceof MixedType) {
			return TrinaryLogic::createYes();
		}

		$original = $this->originalType->hasProperty($propertyName);
		if (!$original->yes()) {
			return $original;
		}

		if ($this->subtractedType->hasProperty($propertyName)->no()) {
			return TrinaryLogic::createYes();
		}

		/*
		 * TODO:
		 * Both the original and the subtracted type have the property,
		 * now we need to determine if *only* the subtracted type has it, or if it is
		 * present without the subtracted type as well.
		 */
		return TrinaryLogic::createMaybe();
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		/*
		 * TODO: possible validate this
		 * Assume for now that this is only called if the property is present, thus we can retrieve
		 * it from the original type.
		 */
		return $this->originalType->getProperty($propertyName, $scope);
	}

	public function canCallMethods(): TrinaryLogic
	{
		// TODO: Implement canCallMethods() method.
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		// TODO: implement hasMethod() method.
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): MethodReflection
	{
		// TODO: Implement getMethod() method.
	}

	public function canAccessConstants(): TrinaryLogic
	{
		// TODO: Implement canAccessConstants() method.
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		// TODO: Implement hasConstant() method.
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		// TODO: Implement getConstant() method.
	}

	public function isIterable(): TrinaryLogic
	{
		// TODO: Implement isIterable() method.
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		// TODO: Implement isIterableAtLeastOnce() method.
	}

	public function getIterableKeyType(): Type
	{
		// TODO: Implement getIterableKeyType() method.
	}

	public function getIterableValueType(): Type
	{
		// TODO: Implement getIterableValueType() method.
	}

	public function isArray(): TrinaryLogic
	{
		// TODO: Implement isArray() method.
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		// TODO: Implement isOffsetAccessible() method.
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		// TODO: Implement hasOffsetValueType() method.
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		// TODO: Implement getOffsetValueType() method.
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType): Type
	{
		// TODO: Implement setOffsetValueType() method.
	}

	public function isCallable(): TrinaryLogic
	{
		// TODO: Implement isCallable() method.
	}

	/**
	 * @inheritDoc
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		// TODO: Implement getCallableParametersAcceptors() method.
	}

	public function isCloneable(): TrinaryLogic
	{
		// TODO: Implement isCloneable() method.
	}

	public function toBoolean(): BooleanType
	{
		// TODO: Implement toBoolean() method.
	}

	public function toNumber(): Type
	{
		// TODO: Implement toNumber() method.
	}

	public function toInteger(): Type
	{
		// TODO: Implement toInteger() method.
	}

	public function toFloat(): Type
	{
		// TODO: Implement toFloat() method.
	}

	public function toString(): Type
	{
		// TODO: Implement toString() method.
	}

	public function toArray(): Type
	{
		// TODO: Implement toArray() method.
	}

	/**
	 * @inheritDoc
	 */
	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		// TODO: Implement inferTemplateTypes() method.
	}

	/**
	 * @inheritDoc
	 */
	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		// TODO: Implement getReferencedTemplateTypes() method.
	}

	/**
	 * @inheritDoc
	 */
	public function traverse(callable $cb): Type
	{
		// TODO: Implement traverse() method.
	}

	/**
	 * @inheritDoc
	 */
	public static function __set_state(array $properties): Type
	{
		// TODO: Implement __set_state() method.
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		// TODO: Implement isSubTypeOf() method.
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		// TODO: Implement isAcceptedBy() method.
	}
}
