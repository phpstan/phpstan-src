<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeReference;
use PHPStan\Type\Generic\TemplateTypeVariance;

/** @api */
interface Type
{

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array;

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic;

	public function isSuperTypeOf(Type $type): TrinaryLogic;

	public function equals(Type $type): bool;

	public function describe(VerbosityLevel $level): string;

	public function canAccessProperties(): TrinaryLogic;

	public function hasProperty(string $propertyName): TrinaryLogic;

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection;

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection;

	public function canCallMethods(): TrinaryLogic;

	public function hasMethod(string $methodName): TrinaryLogic;

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): MethodReflection;

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection;

	public function canAccessConstants(): TrinaryLogic;

	public function hasConstant(string $constantName): TrinaryLogic;

	public function getConstant(string $constantName): ConstantReflection;

	public function isIterable(): TrinaryLogic;

	public function isIterableAtLeastOnce(): TrinaryLogic;

	public function getIterableKeyType(): Type;

	public function getIterableValueType(): Type;

	public function isArray(): TrinaryLogic;

	public function isOffsetAccessible(): TrinaryLogic;

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic;

	public function getOffsetValueType(Type $offsetType): Type;

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type;

	public function unsetOffset(Type $offsetType): Type;

	public function isCallable(): TrinaryLogic;

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array;

	public function isCloneable(): TrinaryLogic;

	public function toBoolean(): BooleanType;

	public function toNumber(): Type;

	public function toInteger(): Type;

	public function toFloat(): Type;

	public function toString(): Type;

	public function toArray(): Type;

	public function isSmallerThan(Type $otherType): TrinaryLogic;

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic;

	public function isString(): TrinaryLogic;

	public function isNumericString(): TrinaryLogic;

	public function isNonEmptyString(): TrinaryLogic;

	public function isLiteralString(): TrinaryLogic;

	public function getSmallerType(): Type;

	public function getSmallerOrEqualType(): Type;

	public function getGreaterType(): Type;

	public function getGreaterOrEqualType(): Type;

	/**
	 * Infers template types
	 *
	 * Infers the real Type of the TemplateTypes found in $this, based on
	 * the received Type.
	 */
	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap;

	/**
	 * Returns the template types referenced by this Type, recursively
	 *
	 * The return value is a list of TemplateTypeReferences, who contain the
	 * referenced template type as well as the variance position in which it was
	 * found.
	 *
	 * For example, calling this on array<Foo<T>,Bar> (with T a template type)
	 * will return one TemplateTypeReference for the type T.
	 *
	 * @param TemplateTypeVariance $positionVariance The variance position in
	 *                                               which the receiver type was
	 *                                               found.
	 *
	 * @return TemplateTypeReference[]
	 */
	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array;

	/**
	 * Traverses inner types
	 *
	 * Returns a new instance with all inner types mapped through $cb. Might
	 * return the same instance if inner types did not change.
	 *
	 * @param callable(Type):Type $cb
	 */
	public function traverse(callable $cb): Type;

	/**
	 * Return the difference with another type, or null if it cannot be represented.
	 *
	 * @see TypeCombinator::remove()
	 */
	public function tryRemove(Type $typeToRemove): ?Type;

	public function generalize(GeneralizePrecision $precision): Type;

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self;

}
